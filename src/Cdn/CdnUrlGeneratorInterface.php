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

namespace Storyblok\Bundle\Cdn;

use Storyblok\Api\Domain\Type\Asset;
use Storyblok\ImageService\Image;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
interface CdnUrlGeneratorInterface
{
    /**
     * @param UrlGeneratorInterface::* $referenceType
     */
    public function generate(Asset|Image $asset, int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string;
}
