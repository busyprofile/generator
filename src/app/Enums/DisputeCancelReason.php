<?php

namespace App\Enums;

use App\Traits\Enumable;

enum DisputeCancelReason: string
{
    use Enumable;

    case REQUIRES_BANK_STATEMENT = 'requires_bank_statement';
    case REQUIRES_VIDEO_PROOF = 'requires_video_proof';
    case WRONG_PAYMENT_REFUND_REQUIRED = 'wrong_payment_refund_required';
    case INCORRECT_AMOUNT_RECEIVED = 'incorrect_amount_received';
    case CANCELLED_BY_PROVIDER = 'cancelled_by_provider';

    public function label(): string
    {
        return match($this) {
            self::REQUIRES_BANK_STATEMENT => 'Требуется выписка',
            self::REQUIRES_VIDEO_PROOF => 'Требуется видео входа в ЛК банка и в сам перевод',
            self::WRONG_PAYMENT_REFUND_REQUIRED => 'По данному ордеру - требует вернуть средства, отправлены по ошибке',
            self::INCORRECT_AMOUNT_RECEIVED => 'Поступила неверная сумма',
            self::CANCELLED_BY_PROVIDER => 'Отклонён провайдером',
        };
    }
} 