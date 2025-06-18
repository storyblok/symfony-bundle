<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Domain\Value;

use OskarStark\Value\TrimmedNonEmptyString;
use Storyblok\Api\Domain\Value\Id;
use Storyblok\Api\Domain\Value\Uuid;
use Webmozart\Assert\Assert;
use function Safe\json_decode;
use function Symfony\Component\String\u;

final readonly class Editable
{
    public Uuid $uuid;
    public Id $id;
    public string $name;
    public string $space;

    public function __construct(string $value)
    {
        TrimmedNonEmptyString::fromString($value);
        Assert::startsWith($value, '<!--#storyblok#');
        Assert::endsWith($value, '-->');

        $values = json_decode(
            u($value)->trimStart('<!--#storyblok#')->trimEnd('-->')->toString(),
            true,
        );

        Assert::keyExists($values, 'uid');
        Assert::uuid($values['uid']);
        $this->uuid = new Uuid($values['uid']);

        Assert::keyExists($values, 'id');
        $this->id = new Id((int) $values['id']);

        Assert::keyExists($values, 'name');
        $this->name = TrimmedNonEmptyString::fromString($values['name'])->toString();

        Assert::keyExists($values, 'space');
        $this->space = $values['space'];
    }

    /**
     * @return array{
     *     uid: string,
     *     id: string,
     *     name: string,
     *     space: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'uid' => $this->uuid->value,
            'id' => (string) $this->id->value,
            'name' => $this->name,
            'space' => $this->space,
        ];
    }
}
