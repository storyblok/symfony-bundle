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

namespace Storyblok\Bundle\Twig;

use Storyblok\Api\Domain\Type\Asset;
use Storyblok\ImageService\Image;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class ImageExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('storyblok_image', $this->image(...)),
        ];
    }

    public function image(Asset $asset, int $width = 0, int $height = 0): Image
    {
        $image = new Image($asset->url);

        if (0 < $width || 0 < $height) {
            $image = $image->resize($width, $height);
        }

        if (null !== $asset->focus) {
            $image = $image->focalPoint($asset->focus);
        }

        return $image;
    }
}
