<?php

namespace App\Contracts;

use App\DTO\Payout\PayoutCreateDTO;
use App\Exceptions\PayoutException;
use App\Models\Payout;
use App\Models\PayoutOffer;
use App\Models\User;
use Illuminate\Http\UploadedFile;

interface PayoutServiceContract
{
    /**
     * @throws PayoutException
     */
    public function createPayout(PayoutCreateDTO $dto): Payout;

    /**
     * @throws PayoutException
     */
    public function finishPayoutByAdmin(Payout $payout): Payout;

    /**
     * @throws PayoutException
     */
    public function finishPayout(Payout $payout, ?UploadedFile $videoReceipt = null): Payout;

    /**
     * @throws PayoutException
     */
    public function refusePayout(Payout $payout, string $reason): Payout;

    /**
     * @throws PayoutException
     */
    public function cancelPayout(Payout $payout, ?string $reason = null): Payout;

    /**
     * @throws PayoutException
     */
    public function passToTrader(Payout $payout): Payout;

    /**
     * @throws PayoutException
     */
    public function getOffersMenu(): array;

    /**
     * @throws PayoutException
     */
    public function addOffer(User $user, array $data): PayoutOffer;

    /**
     * @throws PayoutException
     */
    public function updateOffer(PayoutOffer $payoutOffer, array $data): PayoutOffer;

    /**
     * @throws PayoutException
     */
    public function toggleTraderOffersActivity(User $user): void;
}
