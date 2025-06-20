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

namespace Storyblok\Bundle\Faker\Provider;

use Faker\Provider\Base as BaseProvider;
use function Safe\json_encode;

final class EditableProvider extends BaseProvider
{
    public function storyblokEditable(
        ?string $uid = null,
        ?string $id = null,
        ?string $name = null,
        ?string $space = null,
    ): string {
        $payload = [
            'uid' => $uid ?? $this->generator->uuid(),
            'id' => $id ?? (string) $this->generator->numberBetween(1),
            'name' => $name ?? $this->generator->word(),
            'space' => $space ?? (string) $this->generator->numberBetween(1),
        ];

        return \sprintf('<!--#storyblok#%s-->', json_encode($payload));
    }
}
