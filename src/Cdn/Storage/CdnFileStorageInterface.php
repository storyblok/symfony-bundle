<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn\Storage;

use Storyblok\Bundle\Cdn\Domain\CdnFile;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;

interface CdnFileStorageInterface
{
    public function has(CdnFileId $id, string $filename): bool;

    /**
     * @throws CdnFileNotFoundException
     */
    public function get(CdnFileId $id, string $filename): CdnFile;

    public function set(CdnFileId $id, string $filename, string $content, CdnFileMetadata $metadata): void;

    public function remove(CdnFileId $id, string $filename): void;
}
