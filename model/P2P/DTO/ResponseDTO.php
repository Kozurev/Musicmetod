<?php

namespace Model\P2P\DTO;

use JsonSerializable;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class ResponseDTO implements JsonSerializable
{
    private bool $status;
    private ?string $errorHash;
    private $responseData;

    public function __construct(
        bool $status,
        ?string $errorHash = null,
        $responseData = null
    ) {
        $this->status = $status;
        $this->errorHash = $errorHash;
        $this->responseData = $responseData;
    }

    public static function getSuccess($data): self
    {
        // TODO: добавить валидацию данных
        return new self(true, null, $data);
    }

    public static function getError(?string $errorHash = null): self
    {
        return new self(false, $errorHash, null);
    }

    public function jsonSerialize()
    {
        $responseArray = [
            'status' => $this->status,
        ];
        if (null !== $this->errorHash) {
            $responseArray['errorHash'] = $this->errorHash;
        }
        if (null !== $this->responseData) {
            $responseArray['data'] = $this->responseData;
        }

        return $responseArray;
    }
}
