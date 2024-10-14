<?php

declare(strict_types=1);

namespace Model\P2P\DTO;

class P2PReceiverDTO
{
    private int $userId;
    private int $receiverId;
    private string $authToken;

    public function __construct(
        int $userId,
        int $receiverId,
        string $authToken
    ) {
        $this->userId = $userId;
        $this->receiverId = $receiverId;
        $this->authToken = $authToken;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getReceiverId(): int
    {
        return $this->receiverId;
    }

    public function getAuthToken(): string
    {
        return $this->authToken;
    }
}
