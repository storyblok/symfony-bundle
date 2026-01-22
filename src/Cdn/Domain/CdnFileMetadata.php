<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn\Domain;

use Safe\DateTimeImmutable;

final readonly class CdnFileMetadata implements \JsonSerializable
{
    public function __construct(
        public string $originalUrl,
        public ?string $contentType = null,
        public ?string $etag = null,
        public ?DateTimeImmutable $expiresAt = null,
    ) {
    }

    /**
     * @param array{originalUrl: string, contentType: null|string, etag: null|string, expiresAt: null|string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            originalUrl: $data['originalUrl'],
            contentType: $data['contentType'],
            etag: $data['etag'],
            expiresAt: null !== $data['expiresAt'] ? new DateTimeImmutable($data['expiresAt']) : null,
        );
    }

    public function withDownloadInfo(string $contentType, ?string $etag, DateTimeImmutable $expiresAt): self
    {
        return new self(
            originalUrl: $this->originalUrl,
            contentType: $contentType,
            etag: $etag,
            expiresAt: $expiresAt,
        );
    }

    public function isExpired(): bool
    {
        if (null === $this->expiresAt) {
            return false;
        }

        return $this->expiresAt < new DateTimeImmutable();
    }

    /**
     * @return array{originalUrl: string, contentType: null|string, etag: null|string, expiresAt: null|string}
     */
    public function jsonSerialize(): array
    {
        return [
            'originalUrl' => $this->originalUrl,
            'contentType' => $this->contentType,
            'etag' => $this->etag,
            'expiresAt' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
