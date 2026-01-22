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

namespace Storyblok\Bundle\Tests\Unit\Cdn\Storage;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Storyblok\Bundle\Cdn\Storage\CdnFileFilesystemStorage;
use Storyblok\Bundle\Cdn\Storage\CdnFileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnFileFilesystemStorageTest extends TestCase
{
    private string $storagePath;
    private Filesystem $filesystem;
    private CdnFileFilesystemStorage $storage;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir().'/cdn_storage_test_'.bin2hex(random_bytes(8));
        $this->filesystem = new Filesystem();
        $this->storage = new CdnFileFilesystemStorage($this->filesystem, $this->storagePath);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->storagePath)) {
            $this->filesystem->remove($this->storagePath);
        }
    }

    #[Test]
    public function hasReturnsFalseWhenNothingExists(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        self::assertFalse($this->storage->has($id, 'image.jpg'));
    }

    #[Test]
    public function hasReturnsFalseWhenOnlyMetadataExists(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $this->storage->set($id, 'image.jpg', $metadata);

        self::assertFalse($this->storage->has($id, 'image.jpg'));
    }

    #[Test]
    public function hasReturnsTrueWhenBothMetadataAndFileExist(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
        );

        $this->storage->set($id, 'image.jpg', $metadata, 'file content');

        self::assertTrue($this->storage->has($id, 'image.jpg'));
    }

    #[Test]
    public function getThrowsExceptionWhenMetadataNotFound(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        $this->expectException(CdnFileNotFoundException::class);

        $this->storage->get($id, 'image.jpg');
    }

    #[Test]
    public function getReturnsFileWithNullFileWhenOnlyMetadataExists(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $this->storage->set($id, 'image.jpg', $metadata);

        $cdnFile = $this->storage->get($id, 'image.jpg');

        self::assertSame('https://a.storyblok.com/f/12345/image.jpg', $cdnFile->metadata->originalUrl);
        self::assertNull($cdnFile->file);
    }

    #[Test]
    public function getReturnsFileWithFileWhenBothExist(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"abc123"',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $this->storage->set($id, 'image.jpg', $metadata, 'binary image content');

        $cdnFile = $this->storage->get($id, 'image.jpg');

        self::assertSame('https://a.storyblok.com/f/12345/image.jpg', $cdnFile->metadata->originalUrl);
        self::assertSame('image/jpeg', $cdnFile->metadata->contentType);
        self::assertSame('"abc123"', $cdnFile->metadata->etag);
        self::assertNotNull($cdnFile->file);
        self::assertSame('binary image content', $cdnFile->file->getContent());
    }

    #[Test]
    public function setStoresOnlyMetadataWhenContentIsNull(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $this->storage->set($id, 'image.jpg', $metadata);

        $metadataPath = $this->storagePath.'/'.$id->value.'/image.jpg.json';
        $filePath = $this->storagePath.'/'.$id->value.'/image.jpg';

        self::assertTrue($this->filesystem->exists($metadataPath));
        self::assertFalse($this->filesystem->exists($filePath));
    }

    #[Test]
    public function setStoresBothMetadataAndFileWhenContentProvided(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
        );

        $this->storage->set($id, 'image.jpg', $metadata, 'file content');

        $metadataPath = $this->storagePath.'/'.$id->value.'/image.jpg.json';
        $filePath = $this->storagePath.'/'.$id->value.'/image.jpg';

        self::assertTrue($this->filesystem->exists($metadataPath));
        self::assertTrue($this->filesystem->exists($filePath));
        self::assertSame('file content', $this->filesystem->readFile($filePath));
    }

    #[Test]
    public function setUpdatesExistingMetadata(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        $metadata1 = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );
        $this->storage->set($id, 'image.jpg', $metadata1);

        $metadata2 = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"updated"',
        );
        $this->storage->set($id, 'image.jpg', $metadata2, 'new content');

        $cdnFile = $this->storage->get($id, 'image.jpg');

        self::assertSame('image/jpeg', $cdnFile->metadata->contentType);
        self::assertSame('"updated"', $cdnFile->metadata->etag);
        self::assertNotNull($cdnFile->file);
    }

    #[Test]
    public function removeDeletesDirectory(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $this->storage->set($id, 'image.jpg', $metadata, 'content');

        $directoryPath = $this->storagePath.'/'.$id->value;
        self::assertTrue($this->filesystem->exists($directoryPath));

        $this->storage->remove($id, 'image.jpg');

        self::assertFalse($this->filesystem->exists($directoryPath));
    }

    #[Test]
    public function differentFilenamesStoredSeparately(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        $metadata1 = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );
        $this->storage->set($id, 'original.jpg', $metadata1, 'original content');

        $metadata2 = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );
        $this->storage->set($id, 'resized.jpg', $metadata2, 'resized content');

        self::assertTrue($this->storage->has($id, 'original.jpg'));
        self::assertTrue($this->storage->has($id, 'resized.jpg'));

        $original = $this->storage->get($id, 'original.jpg');
        $resized = $this->storage->get($id, 'resized.jpg');

        self::assertNotNull($original->file);
        self::assertNotNull($resized->file);
        self::assertSame('original content', $original->file->getContent());
        self::assertSame('resized content', $resized->file->getContent());
    }
}
