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

namespace Storyblok\Bundle\Tests\Unit\Cdn\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnFileMetadataTest extends TestCase
{
    #[Test]
    public function constructWithOnlyOriginalUrl(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        self::assertSame('https://a.storyblok.com/f/12345/image.jpg', $metadata->originalUrl);
        self::assertNull($metadata->contentType);
        self::assertNull($metadata->etag);
        self::assertNull($metadata->expiresAt);
    }

    #[Test]
    public function constructWithAllValues(): void
    {
        $expiresAt = new DateTimeImmutable('2025-01-01 12:00:00');

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"abc123"',
            expiresAt: $expiresAt,
        );

        self::assertSame('https://a.storyblok.com/f/12345/image.jpg', $metadata->originalUrl);
        self::assertSame('image/jpeg', $metadata->contentType);
        self::assertSame('"abc123"', $metadata->etag);
        self::assertSame($expiresAt, $metadata->expiresAt);
    }

    #[Test]
    public function fromArray(): void
    {
        $data = [
            'originalUrl' => 'https://a.storyblok.com/f/12345/image.jpg',
            'contentType' => 'image/png',
            'etag' => '"xyz789"',
            'expiresAt' => '2025-06-15T10:30:00+00:00',
        ];

        $metadata = CdnFileMetadata::fromArray($data);

        self::assertSame('https://a.storyblok.com/f/12345/image.jpg', $metadata->originalUrl);
        self::assertSame('image/png', $metadata->contentType);
        self::assertSame('"xyz789"', $metadata->etag);
        self::assertSame('2025-06-15T10:30:00+00:00', $metadata->expiresAt?->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function fromArrayWithNullValues(): void
    {
        $data = [
            'originalUrl' => 'https://a.storyblok.com/f/12345/image.jpg',
            'contentType' => null,
            'etag' => null,
            'expiresAt' => null,
        ];

        $metadata = CdnFileMetadata::fromArray($data);

        self::assertSame('https://a.storyblok.com/f/12345/image.jpg', $metadata->originalUrl);
        self::assertNull($metadata->contentType);
        self::assertNull($metadata->etag);
        self::assertNull($metadata->expiresAt);
    }

    #[Test]
    public function withDownloadInfo(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $expiresAt = new DateTimeImmutable('2025-12-31 23:59:59');
        $enriched = $metadata->withDownloadInfo('image/webp', '"newetag"', $expiresAt);

        self::assertSame('https://a.storyblok.com/f/12345/image.jpg', $enriched->originalUrl);
        self::assertSame('image/webp', $enriched->contentType);
        self::assertSame('"newetag"', $enriched->etag);
        self::assertSame($expiresAt, $enriched->expiresAt);
    }

    #[Test]
    public function withDownloadInfoPreservesOriginalUrl(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/original.jpg',
        );

        $enriched = $metadata->withDownloadInfo('image/jpeg', null, new DateTimeImmutable());

        self::assertSame('https://a.storyblok.com/f/12345/original.jpg', $enriched->originalUrl);
    }

    #[Test]
    public function isExpiredReturnsFalseWhenExpiresAtIsNull(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        self::assertFalse($metadata->isExpired());
    }

    #[Test]
    public function isExpiredReturnsTrueWhenExpired(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            expiresAt: new DateTimeImmutable('2020-01-01 00:00:00'),
        );

        self::assertTrue($metadata->isExpired());
    }

    #[Test]
    public function isExpiredReturnsFalseWhenNotExpired(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            expiresAt: new DateTimeImmutable('+1 year'),
        );

        self::assertFalse($metadata->isExpired());
    }

    #[Test]
    public function jsonSerialize(): void
    {
        $expiresAt = new DateTimeImmutable('2025-03-15T14:30:00+00:00');

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/gif',
            etag: '"etag123"',
            expiresAt: $expiresAt,
        );

        $json = $metadata->jsonSerialize();

        self::assertSame([
            'originalUrl' => 'https://a.storyblok.com/f/12345/image.jpg',
            'contentType' => 'image/gif',
            'etag' => '"etag123"',
            'expiresAt' => '2025-03-15T14:30:00+00:00',
        ], $json);
    }

    #[Test]
    public function jsonSerializeWithNullValues(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $json = $metadata->jsonSerialize();

        self::assertSame([
            'originalUrl' => 'https://a.storyblok.com/f/12345/image.jpg',
            'contentType' => null,
            'etag' => null,
            'expiresAt' => null,
        ], $json);
    }
}
