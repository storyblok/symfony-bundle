<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn\Domain;

use Symfony\Component\HttpFoundation\File\File;

final readonly class CdnFile
{
    public function __construct(
        public File $file,
        public CdnFileMetadata $metadata,
    ) {
    }
}
