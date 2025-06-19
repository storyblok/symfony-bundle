<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Tests\Unit\Editable;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Editable\Domain\Editable;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class EditableTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function uuid(): void
    {
        $faker = self::faker();
        $values = $faker->storyblokEditable(uid: $uuid = $faker->uuid());

        self::assertSame($uuid, (new Editable($values))->uuid->value);
    }

    #[Test]
    public function id(): void
    {
        $faker = self::faker();
        $values = $faker->storyblokEditable(id: $id = (string) $faker->numberBetween(1, 1000));

        self::assertSame((int) $id, (new Editable($values))->id->value);
    }

    #[Test]
    public function validName(): void
    {
        $faker = self::faker();
        $values = $faker->storyblokEditable(name: $name = $faker->word());

        self::assertSame($name, (new Editable($values))->name);
    }

    #[Test]
    public function space(): void
    {
        $faker = self::faker();
        $values = $faker->storyblokEditable(space: $space = (string) $faker->randomNumber());

        self::assertSame($space, (new Editable($values))->space);
    }

    #[Test]
    public function stringable(): void
    {
        $values = self::faker()->storyblokEditable();

        self::assertSame($values, (new Editable($values))->__toString());
        self::assertSame($values, (string) new Editable($values));
    }
}
