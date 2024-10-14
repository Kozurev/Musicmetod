<?php

declare(strict_types=1);

namespace Model\P2P\DTO;

use JsonSerializable;

class TeacherDTO implements JsonSerializable
{
    private int $userId;
    private int $receiverId;
    private string $fio;

    public function __construct(
        int $userId,
        int $receiverId,
        string $fio
    ) {
        $this->userId = $userId;
        $this->receiverId = $receiverId;
        $this->fio = $fio;
    }

    public function jsonSerialize()
    {
        return [
            'user_id' => $this->userId,
            'receiver_id' => $this->receiverId,
            'fio' => $this->fio,
        ];
    }
}
