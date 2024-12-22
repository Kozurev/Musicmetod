<?php

declare(strict_types=1);

namespace Model\P2P\DTO;

use Carbon\Carbon;
use JsonSerializable;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class TransactionDTO implements JsonSerializable
{
    private int $id;
    private TransactionStatusDTO $status;
    private float $amount;
    private int $receiverId;
    private Carbon $createdAt;
    private Carbon $updatedAt;
    private ?string $receiptLink;
    private ?int $receiptFileId;
    private array $extraData;

    public function __construct(
        int $id,
        int $status,
        float $amount,
        int $receiverId,
        string $createdAt,
        string $updatedAt,
        array $extraData = [],
        ?string $receiptLink = null,
        ?int $receiptFileId = null
    ) {
        $this->id = $id;
        $this->status = new TransactionStatusDTO($status);
        $this->amount = $amount;
        $this->receiverId = $receiverId;
        $this->createdAt = Carbon::parse($createdAt);
        $this->updatedAt = Carbon::parse($updatedAt);
        $this->extraData = $extraData;
        $this->receiptLink = $receiptLink;
        $this->receiptFileId = $receiptFileId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): TransactionStatusDTO
    {
        return $this->status;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getReceiverId(): int
    {
        return $this->receiverId;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function getReceiptLink(): ?string
    {
        return $this->receiptLink;
    }

    public function getReceiptFileId(): ?int
    {
        return $this->receiptFileId;
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'status' => $this->getStatus()->getValue(),
            'amount' => $this->getAmount(),
            'receiver_id' => $this->getReceiverId(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->getUpdatedAt()->format('Y-m-d H:i:s'),
            'receipt_link' => $this->getReceiptLink(),
            'receipt_file_id' => $this->getReceiptFileId(),
            'extra_data' => $this->getExtraData(),
        ];
    }
}
