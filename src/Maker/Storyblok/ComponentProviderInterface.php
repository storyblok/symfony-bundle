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

namespace Storyblok\Bundle\Maker\Storyblok;

use Storyblok\Bundle\Maker\RemoteComponent;

/**
 * Lists the components (blocks) available in a Storyblok space.
 *
 * Abstracts the Storyblok Management API so the maker can be unit tested without
 * hitting the network.
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
interface ComponentProviderInterface
{
    /**
     * @return list<RemoteComponent>
     */
    public function components(): array;
}
