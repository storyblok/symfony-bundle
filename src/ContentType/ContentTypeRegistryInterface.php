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

namespace Storyblok\Bundle\ContentType;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
interface ContentTypeRegistryInterface
{
    /**
     * @return list<class-string<ContentTypeInterface>>
     */
    public function all(): array;

    /**
     * @param class-string<ContentTypeInterface> $class
     */
    public function exists(string $class): bool;
}
