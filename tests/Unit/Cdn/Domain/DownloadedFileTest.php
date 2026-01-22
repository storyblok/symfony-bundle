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
use Storyblok\Bundle\Cdn\Domain\DownloadedFile;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class DownloadedFileTest extends TestCase
{
    #[Test]
    public function construct(): void
    {
        $content = 'binary content of the file';
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"abc123"',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $downloadedFile = new DownloadedFile(
            content: $content,
            metadata: $metadata,
        );

        self::assertSame($content, $downloadedFile->content);
        self::assertSame($metadata, $downloadedFile->metadata);
    }

    #[Test]
    public function constructWithEmptyContent(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/empty.txt',
        );

        $downloadedFile = new DownloadedFile(
            content: '',
            metadata: $metadata,
        );

        self::assertSame('', $downloadedFile->content);
    }

    #[Test]
    public function constructWithBinaryContent(): void
    {
        $binaryContent = \random_bytes(100);
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/binary.bin',
            contentType: 'application/octet-stream',
        );

        $downloadedFile = new DownloadedFile(
            content: $binaryContent,
            metadata: $metadata,
        );

        self::assertSame($binaryContent, $downloadedFile->content);
    }
}
