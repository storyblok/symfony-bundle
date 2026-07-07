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
use Webmozart\Assert\Assert;

/**
 * @internal
 *
 * Lists Storyblok components through the Management API {@see ComponentApi}.
 *
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final readonly class ManagementApiComponentProvider implements ComponentProviderInterface
{
    public function __construct(
        private ComponentApi $componentApi,
        private string $spaceId,
        private string $managementToken,
    ) {
    }

    public function components(): array
    {
        Assert::stringNotEmpty($this->managementToken, 'The Storyblok Management API token is empty. Configure "storyblok.management_token" (e.g. set the STORYBLOK_MANAGEMENT_API_TOKEN environment variable to your personal access token).');
        Assert::stringNotEmpty($this->spaceId, 'The Storyblok space id is empty. Configure "storyblok.space_id" (e.g. set the STORYBLOK_SPACE_ID environment variable to your numeric space id).');

        $components = [];

        /** @var Component $component */
        foreach ($this->componentApi->all()->data() as $component) {
            // Only generate nestable blocks (#[AsBlock]); skip content types (is_root only).
            if ($component->isContentType()) {
                continue;
            }

            $components[] = new RemoteComponent($component->name(), $component->getSchema());
        }

        return $components;
    }
}
