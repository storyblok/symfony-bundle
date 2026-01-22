<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn\Domain;

final readonly class DownloadedFile
{
    public function __construct(
        public string $content,
        public CdnFileMetadata $metadata,
    ) {
    }
}
