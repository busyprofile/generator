<?php

namespace App\Exceptions;

class PayoutException extends BaseException
{
    public static function payoutGatewayIsDisabled(): PayoutException
    {
        return new self('Направление выключено.');
    }

    public static function offerAlreadyExists(): PayoutException
    {
        return new self('У вас уже есть предложение для выбранного метода.');
    }

    public static function offerNotExists(): PayoutException
    {
        return new self('Подходящие предложение выплаты не найдено. Попробуйте изменить параметры.');
    }

    public static function freeTraderNotFound(): PayoutException
    {
        return new self('Подходящий трейдер не найден.');
    }

    public static function insufficientBalance(): PayoutException
    {
        return new self('Не достаточно средств на балансе для создания выплаты.');
    }
}
