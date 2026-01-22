<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn\Storage;

use Safe\DateTimeImmutable;
use Storyblok\Bundle\Cdn\Domain\CdnFile;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use function Safe\json_decode;
use function Safe\json_encode;
use function Symfony\Component\String\u;

final readonly class CdnFileFilesystemStorage implements CdnFileStorageInterface
{
    private const string METADATA_FILENAME = 'metadata.json';

    public function __construct(
        private Filesystem $filesystem,
        private string $storagePath,
    ) {
    }

    public function has(CdnFileId $id, string $filename): bool
    {
        return $this->filesystem->exists($this->filePath($id, $filename))
            && $this->filesystem->exists($this->metadataPath($id));
    }

    public function get(CdnFileId $id, string $filename): CdnFile
    {
        $filePath = $this->filePath($id, $filename);
        $metadataPath = $this->metadataPath($id);

        if (!$this->filesystem->exists($filePath) || !$this->filesystem->exists($metadataPath)) {
            throw new CdnFileNotFoundException(\sprintf('File not found: %s/%s', $id->value, $filename));
        }

        $data = json_decode($this->filesystem->readFile($metadataPath), true, 512, \JSON_THROW_ON_ERROR);

        return new CdnFile(
            file: new File($filePath),
            metadata: new CdnFileMetadata(
                contentType: $data['contentType'],
                etag: $data['etag'],
                expiresAt: new DateTimeImmutable($data['expiresAt']),
            ),
        );
    }

    public function set(CdnFileId $id, string $filename, string $content, CdnFileMetadata $metadata): void
    {
        $this->filesystem->dumpFile($this->filePath($id, $filename), $content);
        $this->filesystem->dumpFile($this->metadataPath($id), json_encode($metadata, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT));
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

    private function metadataPath(CdnFileId $id): string
    {
        return $this->filePath($id, self::METADATA_FILENAME);
    }
}
