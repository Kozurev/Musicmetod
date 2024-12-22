<?php

namespace Model\P2P\Exception;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
class BaseP2PException extends \Exception
{
    private string $errorLogHash;

    public function __construct(string $hash, string $message = '', $code = 0, Throwable $previous = null)
    {
        $this->errorLogHash = $hash;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorLogHash(): string
    {
        return $this->errorLogHash;
    }
}
