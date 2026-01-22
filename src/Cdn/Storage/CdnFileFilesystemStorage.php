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

use Storyblok\Bundle\Cdn\Domain\CdnFile;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use function Safe\json_decode;
use function Safe\json_encode;
use function Symfony\Component\String\u;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final readonly class CdnFileFilesystemStorage implements CdnFileStorageInterface
{
    public function __construct(
        private Filesystem $filesystem,
        private string $storagePath,
    ) {
    }

    public function has(CdnFileId $id, string $filename): bool
    {
        return $this->filesystem->exists($this->metadataPath($id, $filename))
            && $this->filesystem->exists($this->filePath($id, $filename));
    }

    public function get(CdnFileId $id, string $filename): CdnFile
    {
        $metadataPath = $this->metadataPath($id, $filename);

        if (!$this->filesystem->exists($metadataPath)) {
            throw new CdnFileNotFoundException(\sprintf('Metadata not found: %s/%s', $id->value, $filename));
        }

        $metadata = CdnFileMetadata::fromArray(json_decode($this->filesystem->readFile($metadataPath), true, 512, \JSON_THROW_ON_ERROR));
        $filePath = $this->filePath($id, $filename);

        return new CdnFile(
            metadata: $metadata,
            file: $this->filesystem->exists($filePath) ? new File($filePath) : null,
        );
    }

    public function set(CdnFileId $id, string $filename, CdnFileMetadata $metadata, ?string $content = null): void
    {
        $this->filesystem->dumpFile($this->metadataPath($id, $filename), json_encode($metadata, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT));

        if (null !== $content) {
            $this->filesystem->dumpFile($this->filePath($id, $filename), $content);
        }
    }

    public function remove(CdnFileId $id, string $filename): void
    {
        $this->filesystem->remove($this->directoryPath($id));
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
