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
use Storyblok\Bundle\Cdn\Storage\CdnFileNotFoundException;
use Storyblok\Bundle\Cdn\Storage\CdnFilesystemStorage;
use Storyblok\Bundle\Cdn\Storage\MetadataNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnFilesystemStorageTest extends TestCase
{
    private string $storagePath;
    private Filesystem $filesystem;
    private CdnFilesystemStorage $storage;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir().'/cdn_storage_test_'.bin2hex(random_bytes(8));
        $this->filesystem = new Filesystem();
        $this->storage = new CdnFilesystemStorage($this->filesystem, $this->storagePath);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->storagePath)) {
            $this->filesystem->remove($this->storagePath);
        }
    }

    #[Test]
    public function hasMetadataReturnsFalseWhenNothingExists(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        self::assertFalse($this->storage->hasMetadata($id, 'image.jpg'));
    }

    #[Test]
    public function hasMetadataReturnsTrueWhenMetadataExists(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $this->storage->setMetadata($id, 'image.jpg', $metadata);

        self::assertTrue($this->storage->hasMetadata($id, 'image.jpg'));
    }

    #[Test]
    public function hasFileReturnsFalseWhenNothingExists(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        self::assertFalse($this->storage->hasFile($id, 'image.jpg'));
    }

    #[Test]
    public function hasFileReturnsFalseWhenOnlyMetadataExists(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $this->storage->setMetadata($id, 'image.jpg', $metadata);

        self::assertFalse($this->storage->hasFile($id, 'image.jpg'));
    }

    #[Test]
    public function hasFileReturnsTrueWhenFileExists(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
        );

        $this->storage->setMetadata($id, 'image.jpg', $metadata);
        $this->storage->setFile($id, 'image.jpg', 'file content');

        self::assertTrue($this->storage->hasFile($id, 'image.jpg'));
    }

    #[Test]
    public function getMetadataThrowsExceptionWhenMetadataNotFound(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        $this->expectException(MetadataNotFoundException::class);

        $this->storage->getMetadata($id, 'image.jpg');
    }

    #[Test]
    public function getMetadataReturnsMetadata(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"abc123"',
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $this->storage->setMetadata($id, 'image.jpg', $metadata);

        $result = $this->storage->getMetadata($id, 'image.jpg');

        self::assertSame('https://a.storyblok.com/f/12345/image.jpg', $result->originalUrl);
        self::assertSame('image/jpeg', $result->contentType);
        self::assertSame('"abc123"', $result->etag);
    }

    #[Test]
    public function getFileThrowsExceptionWhenFileNotFound(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        $this->expectException(CdnFileNotFoundException::class);

        $this->storage->getFile($id, 'image.jpg');
    }

    #[Test]
    public function getFileReturnsFile(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        $this->storage->setFile($id, 'image.jpg', 'binary image content');

        $file = $this->storage->getFile($id, 'image.jpg');

        self::assertSame('binary image content', $file->getContent());
    }

    #[Test]
    public function setMetadataStoresOnlyMetadata(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $this->storage->setMetadata($id, 'image.jpg', $metadata);

        $metadataPath = $this->storagePath.'/'.$id->value.'/image.jpg.json';
        $filePath = $this->storagePath.'/'.$id->value.'/image.jpg';

        self::assertTrue($this->filesystem->exists($metadataPath));
        self::assertFalse($this->filesystem->exists($filePath));
    }

    #[Test]
    public function setFileStoresFile(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        $this->storage->setFile($id, 'image.jpg', 'file content');

        $filePath = $this->storagePath.'/'.$id->value.'/image.jpg';

        self::assertTrue($this->filesystem->exists($filePath));
        self::assertSame('file content', $this->filesystem->readFile($filePath));
    }

    #[Test]
    public function setMetadataUpdatesExistingMetadata(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');

        $metadata1 = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );
        $this->storage->setMetadata($id, 'image.jpg', $metadata1);

        $metadata2 = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
            contentType: 'image/jpeg',
            etag: '"updated"',
        );
        $this->storage->setMetadata($id, 'image.jpg', $metadata2);

        $result = $this->storage->getMetadata($id, 'image.jpg');

        self::assertSame('image/jpeg', $result->contentType);
        self::assertSame('"updated"', $result->etag);
    }

    #[Test]
    public function removeDeletesDirectory(): void
    {
        $id = CdnFileId::generate('https://a.storyblok.com/f/12345/image.jpg');
        $metadata = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );

        $this->storage->setMetadata($id, 'image.jpg', $metadata);
        $this->storage->setFile($id, 'image.jpg', 'content');

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
        $this->storage->setMetadata($id, 'original.jpg', $metadata1);
        $this->storage->setFile($id, 'original.jpg', 'original content');

        $metadata2 = new CdnFileMetadata(
            originalUrl: 'https://a.storyblok.com/f/12345/image.jpg',
        );
        $this->storage->setMetadata($id, 'resized.jpg', $metadata2);
        $this->storage->setFile($id, 'resized.jpg', 'resized content');

        self::assertTrue($this->storage->hasFile($id, 'original.jpg'));
        self::assertTrue($this->storage->hasFile($id, 'resized.jpg'));

        $original = $this->storage->getFile($id, 'original.jpg');
        $resized = $this->storage->getFile($id, 'resized.jpg');

        self::assertSame('original content', $original->getContent());
        self::assertSame('resized content', $resized->getContent());
    }
}
