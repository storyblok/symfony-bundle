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

namespace Storyblok\Bundle\Tests\Unit\Cdn;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Bundle\Cdn\CdnUrlGenerator;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Storyblok\Bundle\Cdn\Storage\CdnStorageInterface;
use Storyblok\Bundle\Routing\Route;
use Storyblok\ImageService\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnUrlGeneratorTest extends TestCase
{
    #[Test]
    public function generateWithAssetStoresMetadataAndReturnsUrl(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::once())
            ->method('hasMetadata')
            ->willReturn(false);
        $storage->expects(self::once())
            ->method('setMetadata')
            ->with(
                self::isInstanceOf(CdnFileId::class),
                '1920x1080-image.jpg',
                self::callback(static fn (CdnFileMetadata $metadata): bool => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg' === $metadata->originalUrl),
            );

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                Route::CDN,
                self::callback(static fn (array $params): bool => isset($params['id'], $params['filename'], $params['extension'])
                    && '1920x1080-image' === $params['filename']
                    && 'jpg' === $params['extension']),
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.com/f/abc123/1920x1080-image.jpg');

        $generator = new CdnUrlGenerator($storage, $urlGenerator);

        $result = $generator->generate($asset);

        self::assertSame('https://example.com/f/abc123/1920x1080-image.jpg', $result);
    }

    #[Test]
    public function generateWithImageStoresMetadataAndReturnsUrl(): void
    {
        $image = new Image('https://a.storyblok.com/f/12345/1920x1080/abc123/photo.webp');

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::once())
            ->method('hasMetadata')
            ->willReturn(false);
        $storage->expects(self::once())
            ->method('setMetadata')
            ->with(
                self::isInstanceOf(CdnFileId::class),
                '1920x1080-photo.webp',
                self::callback(static fn (CdnFileMetadata $metadata): bool => 'https://a.storyblok.com/f/12345/1920x1080/abc123/photo.webp' === $metadata->originalUrl),
            );

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('https://example.com/f/def456/1920x1080-photo.webp');

        $generator = new CdnUrlGenerator($storage, $urlGenerator);

        $result = $generator->generate($image);

        self::assertSame('https://example.com/f/def456/1920x1080-photo.webp', $result);
    }

    #[Test]
    public function generateWithResizedImageStoresCorrectUrl(): void
    {
        $image = (new Image('https://a.storyblok.com/f/12345/1920x1080/abc123/photo.jpg'))
            ->resize(640, 480);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::once())
            ->method('hasMetadata')
            ->willReturn(false);
        $storage->expects(self::once())
            ->method('setMetadata')
            ->with(
                self::isInstanceOf(CdnFileId::class),
                '640x480-photo.jpg',
                self::callback(static fn (CdnFileMetadata $metadata): bool => str_contains($metadata->originalUrl, '/m/640x480')),
            );

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                Route::CDN,
                self::callback(static fn (array $params): bool => '640x480-photo' === $params['filename']
                    && 'jpg' === $params['extension']),
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.com/f/xyz789/640x480-photo.jpg');

        $generator = new CdnUrlGenerator($storage, $urlGenerator);

        $result = $generator->generate($image);

        self::assertSame('https://example.com/f/xyz789/640x480-photo.jpg', $result);
    }

    #[Test]
    public function generateDoesNotStoreWhenFileAlreadyExists(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::once())
            ->method('hasMetadata')
            ->willReturn(true);
        $storage->expects(self::never())
            ->method('setMetadata');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('https://example.com/f/abc123/1920x1080-image.jpg');

        $generator = new CdnUrlGenerator($storage, $urlGenerator);

        $result = $generator->generate($asset);

        self::assertSame('https://example.com/f/abc123/1920x1080-image.jpg', $result);
    }

    #[Test]
    public function generateWithRelativePathReferenceType(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('hasMetadata')->willReturn(true);

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                Route::CDN,
                self::anything(),
                UrlGeneratorInterface::RELATIVE_PATH,
            )
            ->willReturn('f/abc123/1920x1080-image.jpg');

        $generator = new CdnUrlGenerator($storage, $urlGenerator);

        $result = $generator->generate($asset, UrlGeneratorInterface::RELATIVE_PATH);

        self::assertSame('f/abc123/1920x1080-image.jpg', $result);
    }

    #[Test]
    public function generateWithAbsolutePathReferenceType(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('hasMetadata')->willReturn(true);

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                Route::CDN,
                self::anything(),
                UrlGeneratorInterface::ABSOLUTE_PATH,
            )
            ->willReturn('/f/abc123/1920x1080-image.jpg');

        $generator = new CdnUrlGenerator($storage, $urlGenerator);

        $result = $generator->generate($asset, UrlGeneratorInterface::ABSOLUTE_PATH);

        self::assertSame('/f/abc123/1920x1080-image.jpg', $result);
    }

    #[Test]
    public function generateUsesCorrectRouteParameters(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/document.pdf',
        ]);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('hasMetadata')->willReturn(false);
        $storage->method('setMetadata');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                Route::CDN,
                self::callback(static function (array $params): bool {
                    return \is_string($params['id'])
                        && 16 === \strlen($params['id'])
                        && 'document' === $params['filename']
                        && 'pdf' === $params['extension'];
                }),
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.com/f/abc123/document.pdf');

        $generator = new CdnUrlGenerator($storage, $urlGenerator);

        $generator->generate($asset);
    }

    #[Test]
    public function generateSameAssetTwiceOnlyStoresOnce(): void
    {
        $asset = new Asset([
            'id' => 12345,
            'filename' => 'https://a.storyblok.com/f/12345/1920x1080/abc123/image.jpg',
        ]);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::exactly(2))
            ->method('hasMetadata')
            ->willReturnOnConsecutiveCalls(false, true);
        $storage->expects(self::once())
            ->method('setMetadata');

        $urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->willReturn('https://example.com/f/abc123/1920x1080-image.jpg');

        $generator = new CdnUrlGenerator($storage, $urlGenerator);

        $generator->generate($asset);
        $generator->generate($asset);
    }
}
