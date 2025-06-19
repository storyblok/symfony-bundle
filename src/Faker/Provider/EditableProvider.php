<?php

declare(strict_types=1);

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
