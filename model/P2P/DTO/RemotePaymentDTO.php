<?php

namespace Model\P2P\DTO;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
final class RemotePaymentDTO implements \JsonSerializable
{
    private PaymentDTO $paymentDTO;
    private TransactionDTO $transactionDTO;

    public function __construct(PaymentDTO $paymentDTO, TransactionDTO $transactionDTO)
    {
        $this->paymentDTO = $paymentDTO;
        $this->transactionDTO = $transactionDTO;
    }

    public function getPaymentDTO(): PaymentDTO
    {
        return $this->paymentDTO;
    }

    public function getTransactionDTO(): TransactionDTO
    {
        return $this->transactionDTO;
    }

    public function jsonSerialize()
    {
        return [
            'payment' => $this->paymentDTO,
            'transaction' => $this->transactionDTO,
        ];
    }
}
