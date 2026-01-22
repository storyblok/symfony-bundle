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

namespace Storyblok\Bundle\Cdn\Domain;

use Storyblok\Api\Domain\Type\Asset;
use Storyblok\ImageService\Image;

/**
 * @internal
 */
final readonly class AssetInfo
{
    public CdnFileId $id;
    public string $filename;
    public string $fullFilename;
    public string $extension;
    public string $url;

    public function __construct(Asset|Image $asset)
    {
        if ($asset instanceof Image) {
            $url = $asset->toString();
            $width = $asset->getWidth();
            $height = $asset->getHeight();
            $name = $asset->getName();
            $extension = $asset->getExtension();
        } else {
            $url = $asset->url;
            $width = $asset->width;
            $height = $asset->height;
            $name = $asset->name;
            $extension = $asset->extension;
        }

        $this->id = CdnFileId::generate($url);

        $dimensions = null;

        if (0 < $width || 0 < $height) {
            $dimensions = \sprintf('%dx%d', $width, $height);
        }

        $this->url = $url;
        $this->extension = $extension;
        $this->filename = null !== $dimensions ? \sprintf('%s-%s', $dimensions, $name) : $name;
        $this->fullFilename = \sprintf('%s.%s', $this->filename, $extension);
    }
}
