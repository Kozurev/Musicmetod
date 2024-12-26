<?php

namespace Model\P2P\DTO;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
final class PaymentDTO implements \JsonSerializable
{
    private \Payment $payment;

    public function __construct(\Payment $payment)
    {
        $this->payment = $payment;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->payment->getId(),
        ];
    }
}
