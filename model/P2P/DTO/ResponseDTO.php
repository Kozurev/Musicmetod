<?php

namespace Model\P2P\DTO;

use JsonSerializable;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class ResponseDTO implements JsonSerializable
{
    private bool $status;
    private ?string $errorHash = null;
    private ?string $errorMessage = null;
    private $responseData;

    public function __construct(
        bool $status,
        ?string $errorHash = null,
        ?string $errorMessage = null,
        $responseData = null
    ) {
        $this->status = $status;
        $this->errorHash = $errorHash;
        $this->errorMessage = $errorMessage;
        $this->responseData = $responseData;
    }

    public static function getSuccess($data): self
    {
        // TODO: добавить валидацию данных
        return new self(true, null, null, $data);
    }

    public static function getErrorByHash(string $errorHash): self
    {
        return new self(false, $errorHash, null, null);
    }

    public static function getErrorByMessage(string $errorMessage): self
    {
        return new self(false, null, $errorMessage, null);
    }

    public function jsonSerialize()
    {
        $responseArray = [
            'status' => $this->status,
        ];
        if (null !== $this->errorHash) {
            $responseArray['errorHash'] = $this->errorHash;
        }
        if (null !== $this->errorMessage) {
            $responseArray['errorMessage'] = $this->errorMessage;
        }
        if (null !== $this->responseData) {
            $responseArray['data'] = $this->responseData;
        }

        return $responseArray;
    }
}
