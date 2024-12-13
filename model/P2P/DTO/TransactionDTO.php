<?php

declare(strict_types=1);

namespace Model\P2P\DTO;

use Carbon\Carbon;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class TransactionDTO
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
}
