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

namespace Storyblok\Bundle\Cdn\Storage;

use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use function Safe\json_decode;
use function Safe\json_encode;
use function Symfony\Component\String\u;

/**
 * Filesystem-based implementation of CDN file storage.
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final readonly class CdnFilesystemStorage implements CdnStorageInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private string $storagePath,
    ) {
    }

    public function hasMetadata(CdnFileId $id, string $filename): bool
    {
        return $this->filesystem->exists($this->metadataPath($id, $filename));
    }

    public function hasFile(CdnFileId $id, string $filename): bool
    {
        return $this->filesystem->exists($this->filePath($id, $filename));
    }

    public function getMetadata(CdnFileId $id, string $filename): CdnFileMetadata
    {
        $metadataPath = $this->metadataPath($id, $filename);

        if (!$this->filesystem->exists($metadataPath)) {
            throw new MetadataNotFoundException(\sprintf('Metadata not found: %s/%s', $id->value, $filename));
        }

        return CdnFileMetadata::fromArray(
            json_decode($this->filesystem->readFile($metadataPath), true, 512, \JSON_THROW_ON_ERROR),
        );
    }

    public function getFile(CdnFileId $id, string $filename): File
    {
        $filePath = $this->filePath($id, $filename);

        if (!$this->filesystem->exists($filePath)) {
            throw new CdnFileNotFoundException(\sprintf('File not found: %s/%s', $id->value, $filename));
        }

        return new File($filePath);
    }

    public function setMetadata(CdnFileId $id, string $filename, CdnFileMetadata $metadata): void
    {
        $this->filesystem->dumpFile(
            $this->metadataPath($id, $filename),
            json_encode($metadata, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT),
        );
    }

    public function setFile(CdnFileId $id, string $filename, string $content): void
    {
        $this->filesystem->dumpFile($this->filePath($id, $filename), $content);
    }

    public function remove(CdnFileId $id, string $filename): void
    {
        $directoryPath = $this->directoryPath($id);

        if ($this->filesystem->exists($directoryPath)) {
            $this->filesystem->remove($directoryPath);
        }
    }

    private function directoryPath(CdnFileId $id): string
    {
        return u($this->storagePath)
            ->ensureEnd('/')
            ->append($id->value)
            ->toString();
    }

    private function filePath(CdnFileId $id, string $filename): string
    {
        return u($this->directoryPath($id))
            ->ensureEnd('/')
            ->append($filename)
            ->toString();
    }

    private function metadataPath(CdnFileId $id, string $filename): string
    {
        return $this->filePath($id, \sprintf('%s.json', $filename));
    }
}
