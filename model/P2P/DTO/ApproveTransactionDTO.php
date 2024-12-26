<?php

namespace Model\P2P\DTO;

/**
 * @author Marketplace Team <trade-services-dev@b2b-center.ru>
 */
final class ApproveTransactionDTO
{
    private ?string $receiptLink = null;
    private ?string $receiptFile = null;
    private ?string $mime = null;
    private ?string $originalClientName = null;

    private function __construct(
        ?string $receiptLink = null,
        ?string $receiptFilePath = null,
        ?string $mime = null,
        ?string $originalFileName = null
    ) {
        $this->receiptLink = $receiptLink;
        $this->receiptFile = $receiptFilePath;
        $this->mime = $mime;
        $this->originalFileName = $originalFileName;
    }

    public static function getByReceiptLink(string $receiptLink): self
    {
        return new self($receiptLink, null);
    }

    public static function getByReceiptFile(string $receiptFile, ?string $mime, ?string $originalFileName): self
    {
        return new self(null, $receiptFile, $mime, $originalFileName);
    }

    public function getReceiptLink(): ?string
    {
        return $this->receiptLink;
    }

    public function getReceiptFile(): ?string
    {
        return $this->receiptFile;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }
}
