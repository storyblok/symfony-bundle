<?php

declare(strict_types=1);

/**
 * This file is part of storyblok/symfony-bundle.
 *
 * (c) Storyblok GmbH <info@storyblok.com>
 * in cooperation with SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\Cdn\Domain;

use Safe\DateTimeImmutable;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
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
            etag: self::normalizeEtag($data['etag']),
            expiresAt: null !== $data['expiresAt'] ? new DateTimeImmutable($data['expiresAt']) : null,
        );
    }

    public function withDownloadInfo(string $contentType, ?string $etag, DateTimeImmutable $expiresAt): self
    {
        return new self(
            originalUrl: $this->originalUrl,
            contentType: $contentType,
            etag: self::normalizeEtag($etag),
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

    /**
     * Normalize ETag by stripping surrounding quotes while preserving weak indicator.
     * Converts: "value" → value, W/"value" → W/value.
     */
    private static function normalizeEtag(?string $etag): ?string
    {
        if (null === $etag) {
            return null;
        }

        $weak = '';

        if (str_starts_with($etag, 'W/')) {
            $weak = 'W/';
            $etag = substr($etag, 2);
        }

        return $weak.trim($etag, '"');
    }
}
