<?php

namespace Model\P2P\DTO;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
final class RemotePaymentApproveDTO implements \JsonSerializable
{
    private bool $status;
    private PaymentDTO $teacherPaymentDTO;
    private PaymentDTO $clientPaymentDTO;
    private TransactionDTO $transactionDTO;

    public function __construct(
        bool $status,
        PaymentDTO $teacherPaymentDTO,
        PaymentDTO $clientPaymentDTO,
        TransactionDTO $transactionDTO
    ) {
        $this->status = $status;
        $this->teacherPaymentDTO = $teacherPaymentDTO;
        $this->clientPaymentDTO = $clientPaymentDTO;
        $this->transactionDTO = $transactionDTO;
    }

    public function isApproved(): bool
    {
        return $this->status;
    }

    public function getTeacherPaymentDTO(): PaymentDTO
    {
        return $this->teacherPaymentDTO;
    }

    public function getClientPaymentDTO(): PaymentDTO
    {
        return $this->clientPaymentDTO;
    }

    public function getTransactionDTO(): TransactionDTO
    {
        return $this->transactionDTO;
    }

    public function jsonSerialize()
    {
        return [
            'is_approved' => $this->isApproved(),
            'teacher_payment' => $this->teacherPaymentDTO,
            'client_payment' => $this->clientPaymentDTO,
            'transaction' => $this->transactionDTO,
        ];
    }
}
