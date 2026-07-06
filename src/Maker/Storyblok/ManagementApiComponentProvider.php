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
use Storyblok\ManagementApi\Data\Component;
use Storyblok\ManagementApi\Endpoints\ComponentApi;

/**
 * Lists Storyblok components through the Management API {@see ComponentApi}.
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final readonly class ManagementApiComponentProvider implements ComponentProviderInterface
{
    public function __construct(
        private ComponentApi $componentApi,
    ) {
    }

    public function components(): array
    {
        $components = [];

        /** @var Component $component */
        foreach ($this->componentApi->all()->data() as $component) {
            $components[] = new RemoteComponent($component->name(), $component->getSchema());
        }

        return $components;
    }
}
