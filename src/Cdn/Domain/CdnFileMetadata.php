<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn\Domain;

use Safe\DateTimeImmutable;

final readonly class CdnFileMetadata implements \JsonSerializable
{
    public function __construct(
        public string $contentType,
        public ?string $etag,
        public DateTimeImmutable $expiresAt,
    ) {
    }

    /**
     * @param array{contentType: string, etag: null|string, expiresAt: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            contentType: $data['contentType'],
            etag: $data['etag'],
            expiresAt: new DateTimeImmutable($data['expiresAt']),
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    /**
     * @return array{contentType: string, etag: null|string, expiresAt: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'contentType' => $this->contentType,
            'etag' => $this->etag,
            'expiresAt' => $this->expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
