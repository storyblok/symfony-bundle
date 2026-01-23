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
use Storyblok\Bundle\Cdn\Domain\CdnFile;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Symfony\Component\HttpFoundation\File\File;
use function Safe\file_put_contents;
use function Safe\tempnam;
use function Safe\unlink;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnFileTest extends TestCase
{
    #[Test]
    public function constructWithMetadataOnly(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $cdnFile = new CdnFile(metadata: $metadata);

        self::assertSame($metadata, $cdnFile->metadata);
        self::assertNull($cdnFile->file);
    }

    #[Test]
    public function constructWithMetadataAndFile(): void
    {
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
        );

        $tempFile = tempnam(sys_get_temp_dir(), 'cdn_test_');
        file_put_contents($tempFile, 'test content');
        $file = new File($tempFile);

        try {
            $cdnFile = new CdnFile(metadata: $metadata, file: $file);

            self::assertSame($metadata, $cdnFile->metadata);
            self::assertSame($file, $cdnFile->file);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
