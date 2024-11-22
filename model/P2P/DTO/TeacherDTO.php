<?php

declare(strict_types=1);

namespace Model\P2P\DTO;

use JsonSerializable;

class TeacherDTO implements JsonSerializable
{
    private int $userId;
    private string $fio;

    public function __construct(
        int $userId,
        string $fio
    ) {
        $this->userId = $userId;
        $this->fio = $fio;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function jsonSerialize()
    {
        return [
            'user_id' => $this->userId,
            'fio' => $this->fio,
        ];
    }
}
