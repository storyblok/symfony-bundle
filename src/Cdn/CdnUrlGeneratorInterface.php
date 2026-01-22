<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Cdn;

use Storyblok\Api\Domain\Type\Asset;
use Storyblok\ImageService\Image;

interface CdnUrlGeneratorInterface
{
    public function generate(Asset|Image $asset): string;
}
