<?php

namespace Model\P2P\DTO;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class RemotePaymentDTO
{
    private \Payment $payment;
    private TransactionDTO $transactionDTO;

    public function __construct(\Payment $payment, TransactionDTO $transactionDTO)
    {
        $this->payment = $payment;
        $this->transactionDTO = $transactionDTO;
    }

    public function getPayment(): \Payment
    {
        return $this->payment;
    }

    public function getTransactionDTO(): TransactionDTO
    {
        return $this->transactionDTO;
    }
}
