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

namespace Storyblok\Bundle\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Storyblok\Bundle\Cdn\Domain\DownloadedFile;
use Storyblok\Bundle\Cdn\Download\FileDownloaderInterface;
use Storyblok\Bundle\Cdn\Storage\CdnStorageInterface;
use Storyblok\Bundle\Cdn\Storage\MetadataNotFoundException;
use Storyblok\Bundle\Controller\CdnController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function Safe\file_put_contents;
use function Safe\tempnam;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnControllerTest extends TestCase
{
    private Filesystem $filesystem;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir().'/cdn_controller_test_'.bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->tempDir)) {
            $this->filesystem->remove($this->tempDir);
        }
    }

    #[Test]
    public function throwsNotFoundWhenMetadataNotFound(): void
    {
        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::once())
            ->method('getMetadata')
            ->willThrowException(new MetadataNotFoundException('Not found'));

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, null, null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Asset not found');

        $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');
    }

    #[Test]
    public function returnsFileWhenAlreadyDownloaded(): void
    {
        $tempFile = $this->createTempFile('binary content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"abc123"',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);
        $storage->expects(self::once())
            ->method('hasFile')
            ->willReturn(true);
        $storage->expects(self::once())
            ->method('getFile')
            ->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);
        $downloader->expects(self::never())->method('download');

        $controller = new CdnController($storage, $downloader, null, null, null);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function downloadsFileWhenNotYetDownloaded(): void
    {
        $tempFile = $this->createTempFile('downloaded content');
        $file = new File($tempFile);

        $metadataWithoutFile = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $downloadedMetadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"downloaded"',
            expiresAt: new DateTimeImmutable('+1 day'),
        );
        $downloadedFile = new DownloadedFile('downloaded content', $downloadedMetadata);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadataWithoutFile);
        $storage->expects(self::once())
            ->method('hasFile')
            ->willReturn(false);
        $storage->expects(self::once())
            ->method('setMetadata')
            ->with(
                self::isInstanceOf(CdnFileId::class),
                'image.jpg',
                self::isInstanceOf(CdnFileMetadata::class),
            );
        $storage->expects(self::once())
            ->method('setFile')
            ->with(
                self::isInstanceOf(CdnFileId::class),
                'image.jpg',
                'downloaded content',
            );
        $storage->expects(self::once())
            ->method('getFile')
            ->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);
        $downloader->expects(self::once())
            ->method('download')
            ->with('https://a.storyblok.com/f/12345/image.jpg')
            ->willReturn($downloadedFile);

        $controller = new CdnController($storage, $downloader, null, null, null);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function throwsExceptionWhenDownloadedMetadataIncomplete(): void
    {
        $metadataWithoutFile = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $incompleteMetadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: null,
            expiresAt: null,
        );
        $downloadedFile = new DownloadedFile('content', $incompleteMetadata);

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadataWithoutFile);
        $storage->method('hasFile')->willReturn(false);

        $downloader = self::createMock(FileDownloaderInterface::class);
        $downloader->method('download')->willReturn($downloadedFile);

        $controller = new CdnController($storage, $downloader, null, null, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Downloaded file metadata is incomplete');

        $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');
    }

    #[Test]
    public function setsContentTypeHeader(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.webp',
            contentType: 'image/webp',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, null, null);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'webp');

        self::assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function setsEtagHeaderWhenConfigured(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"etag-value-123"',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, null, null, true);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        self::assertSame('"etag-value-123"', $response->getEtag());
    }

    #[Test]
    public function doesNotSetEtagHeaderWhenNotConfigured(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"etag-value-123"',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, null, null, null);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        self::assertNull($response->getEtag());
    }

    #[Test]
    public function setsMaxAgeWhenConfigured(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, 3600, null, null);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        self::assertSame(3600, $response->getMaxAge());
    }

    #[Test]
    public function setsSharedMaxAgeWhenConfigured(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, 7200, null);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        self::assertStringContainsString('s-maxage=7200', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function setsPublicCacheDirective(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, null, true);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        self::assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function setsPrivateCacheDirective(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, null, false);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        self::assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function combinesAllCacheDirectives(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, 3600, 7200, true);

        $response = $controller->__invoke(new Request(), 'ef7436441c4defbf', 'image', 'jpg');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        self::assertStringContainsString('public', $cacheControl);
        self::assertStringContainsString('max-age=3600', $cacheControl);
        self::assertStringContainsString('s-maxage=7200', $cacheControl);
    }

    #[Test]
    public function returns304WhenEtagMatches(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"etag-value-123"',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->method('getMetadata')->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, null, null, true);

        $request = new Request();
        $request->headers->set('If-None-Match', '"etag-value-123"');

        $response = $controller->__invoke($request, 'ef7436441c4defbf', 'image', 'jpg');

        self::assertSame(304, $response->getStatusCode());
    }

    #[Test]
    public function constructsFullFilenameFromParts(): void
    {
        $tempFile = $this->createTempFile('content');
        $file = new File($tempFile);

        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/document.pdf',
            contentType: 'application/pdf',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $storage = self::createMock(CdnStorageInterface::class);
        $storage->expects(self::once())
            ->method('getMetadata')
            ->with(
                self::isInstanceOf(CdnFileId::class),
                'my-document.pdf',
            )
            ->willReturn($metadata);
        $storage->method('hasFile')->willReturn(true);
        $storage->method('getFile')->willReturn($file);

        $downloader = self::createMock(FileDownloaderInterface::class);

        $controller = new CdnController($storage, $downloader, null, null, null);

        $controller->__invoke(new Request(), 'ef7436441c4defbf', 'my-document', 'pdf');
    }

    private function createTempFile(string $content): string
    {
        $tempFile = tempnam($this->tempDir, 'cdn_test_');
        file_put_contents($tempFile, $content);

        return $tempFile;
    }
}
