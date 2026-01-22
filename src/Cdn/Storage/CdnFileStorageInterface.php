<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn\Storage;

use Storyblok\Bundle\Cdn\Domain\CdnFile;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;
use Storyblok\Bundle\Cdn\Domain\CdnFileMetadata;

/**
 * @experimental
 */
interface CdnFileStorageInterface
{
    public function has(CdnFileId $id, string $filename): bool;

    /**
     * @throws CdnFileNotFoundException when metadata doesn't exist
     */
    public function get(CdnFileId $id, string $filename): CdnFile;

    /**
     * Stores metadata and optionally file content.
     * When content is null, only metadata is stored (lazy download state).
     */
    public function set(CdnFileId $id, string $filename, CdnFileMetadata $metadata, ?string $content = null): void;

    public function remove(CdnFileId $id, string $filename): void;
}
