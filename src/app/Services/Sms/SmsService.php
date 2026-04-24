<?php

namespace App\Services\Sms;

use App\Contracts\SmsServiceContract;
use App\DTO\SMS\SmsDTO;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use App\Exceptions\SmsServiceException;
use App\Models\Order;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Sms\Utils\NormalizeMessage;

class SmsService implements SmsServiceContract
{
    /**
     * @throws SmsServiceException
     */
    public function handleSms(SmsDTO $sms): void
    {
        $sender = $this->normalizeMessage($sms->sender);

        $device = cache()->remember(
            'user_device_' . $sms->deviceID,
            now()->addMinutes(10),
            function () use ($sms) {
                return UserDevice::where('id', $sms->deviceID)->with('user')->first();
            }
        );
        $user = $device->user;

        $smsLog = $this->logSms($sms, $device, $user);

        $result = (new Parser())->parse($sender, $sms->message);

        if (empty($result)) {
            \Log::info('SMS не удалось распарсить', [
                'sender' => $sender,
                'message' => $sms->message,
                'user_id' => $user->id,
                'device_id' => $device->id,
            ]);
            return;
        }

        \Log::info('SMS успешно распарсен', [
            'sender' => $sender,
            'parsed_amount' => $result->amount->toBeauty(),
            'parsed_currency' => $result->amount->getCurrency()->getCode(),
            'payment_gateway' => $result->paymentGateway->name,
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        /**
         * @var Order|NULL $order
         */

        $order = queries()
            ->order()
            ->findPending($result->amount, $user, $result->paymentGateway, $device);

        if (! $order) {
            \Log::info('Соответствующий ордер не найден', [
                'parsed_amount' => $result->amount->toBeauty(),
                'parsed_currency' => $result->amount->getCurrency()->getCode(),
                'payment_gateway' => $result->paymentGateway->name,
                'user_id' => $user->id,
                'device_id' => $device->id,
            ]);
            return;
        }

        if ($order && $order->status->equals(OrderStatus::PENDING)) {
            \Log::info('Автоматическое подтверждение ордера по SMS', [
                'order_id' => $order->id,
                'order_uuid' => $order->uuid,
                'order_amount' => $order->amount->toBeauty(),
                'order_currency' => $order->currency->getCode(),
                'sms_amount' => $result->amount->toBeauty(),
                'sms_currency' => $result->amount->getCurrency()->getCode(),
                'user_id' => $user->id,
            ]);

            services()->order()->finishOrderAsSuccessful($order->id, OrderSubStatus::SUCCESSFULLY_PAID);

            $smsLog->update([
                'order_id' => $order->id,
            ]);
        }
    }

    protected function logSms(SmsDTO $sms, UserDevice $device, User $user): SmsLog
    {
        return SmsLog::create([
            'sender' => $this->normalizeMessage($sms->sender),
            'message' => $this->normalizeMessage($sms->message),
            'parsing_result' => (new Parser())->parseRaw($sms->message),
            'timestamp' => $sms->timestamp / 1000,
            'type' => $sms->type,
            'user_device_id' => $device->id,
            'user_id' => $user->id,
        ]);
    }

    protected function normalizeMessage(string $message): string
    {
        return NormalizeMessage::normalize($message);
    }
}
