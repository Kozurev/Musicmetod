<?php

namespace Model\P2P\DTO;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class CreateTransactionDTO
{
    private int $receiverId;
    private float $amount;
    private int $clientId;
    private int $teacherId;
    private int $paymentId;

    public function __construct(
        int $receiverId,
        float $amount,
        int $clientId,
        int $teacherId,
        int $paymentId
    ) {
        $this->receiverId = $receiverId;
        $this->amount = $amount;
        $this->clientId = $clientId;
        $this->teacherId = $teacherId;
        $this->paymentId = $paymentId;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'receiver_id' => $this->receiverId,
            'extra_data' => [
                'client_id' => $this->clientId,
                'teacher_id' => $this->teacherId,
                'payment_id' => $this->paymentId,
            ],
        ];
    }
}
