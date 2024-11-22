<?php

declare(strict_types=1);

namespace Model\P2P\DTO;

use JsonSerializable;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class ReceiverPaymentDataDTO implements JsonSerializable
{
    private int $receiverId;
    private ?string $card_number;
    private ?string $phone_number;
    private ?string $comment;

    public function __construct(
        int $receiverId,
        ?string $card_number,
        ?string $phone_number,
        ?string $comment
    ) {
        $this->receiverId = $receiverId;
        $this->card_number = $card_number;
        $this->phone_number = $phone_number;
        $this->comment = $comment;
    }

    public function jsonSerialize()
    {
        return [
            'receiver_id' => $this->receiverId,
            'card_number' => $this->card_number,
            'phone_number' => $this->phone_number,
            'comment' => $this->comment,
        ];
    }
}
