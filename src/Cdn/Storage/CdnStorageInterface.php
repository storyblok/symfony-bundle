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
use Symfony\Component\HttpFoundation\File\File;

/**
 * Unified storage interface for CDN files.
 *
 * @experimental
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
interface CdnStorageInterface
{
    /**
     * Check if metadata exists for the given file.
     */
    public function hasMetadata(CdnFileId $id, string $filename): bool;

    /**
     * Check if the actual file content exists.
     */
    public function hasFile(CdnFileId $id, string $filename): bool;

    /**
     * Get metadata for the given file.
     *
     * @throws MetadataNotFoundException when metadata doesn't exist
     */
    public function getMetadata(CdnFileId $id, string $filename): CdnFileMetadata;

    /**
     * Get the actual file.
     *
     * @throws CdnFileNotFoundException when file doesn't exist
     */
    public function getFile(CdnFileId $id, string $filename): File;

    /**
     * Store metadata for the given file.
     */
    public function setMetadata(CdnFileId $id, string $filename, CdnFileMetadata $metadata): void;

    /**
     * Store the actual file content.
     */
    public function setFile(CdnFileId $id, string $filename, string $content): void;

    /**
     * Remove the entire directory for the given file (both metadata and file content).
     */
    public function remove(CdnFileId $id, string $filename): void;
}
