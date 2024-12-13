<?php

namespace Model\P2P\DTO;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class TransactionStatusDTO
{
    private const STATUS_PENDING = 1;
    private const STATUS_APPROVED_BY_RECEIVER = 2;
    private const STATUS_REJECTED_BY_RECEIVER = 4;

    private int $status;

    public function __construct(int $status)
    {
        $this->status = $status;
    }

    public function getValue(): int
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED_BY_RECEIVER;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED_BY_RECEIVER;
    }
}
