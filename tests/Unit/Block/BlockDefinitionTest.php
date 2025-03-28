<?php

declare(strict_types=1);

/**
 * This file is part of sensiolabs-de/storyblok-bundle.
 *
 * (c) SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\Tests\Unit\Block;

use Ergebnis\DataProvider\StringProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\BlockDefinition;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class BlockDefinitionTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function validName(): void
    {
        $faker = self::faker();
        $expected = $faker->word();

        self::assertSame($expected, (new BlockDefinition($expected, SampleBlock::class, 'sample/block.html.twig'))->name);
    }

    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    #[Test]
    public function nameInvalid(string $value): void
    {
        self::expectException(\InvalidArgumentException::class);

        new BlockDefinition($value, SampleBlock::class, 'sample/block.html.twig');
    }

    #[Test]
    public function className(): void
    {
        $faker = self::faker();
        $expected = SampleBlock::class;

        self::assertSame($expected, (new BlockDefinition($faker->word(), $expected, 'sample/block.html.twig'))->className);
    }

    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    #[Test]
    public function classNameInvalid(string $value): void
    {
        self::expectException(\InvalidArgumentException::class);

        new BlockDefinition(self::faker()->word(), $value, 'sample/block.html.twig');
    }

    #[Test]
    public function classNameClassMustExist(): void
    {
        $faker = self::faker();
        self::expectException(\InvalidArgumentException::class);

        new BlockDefinition($faker->word(), $faker->word(), 'sample/block.html.twig');
    }

    #[Test]
    public function template(): void
    {
        $faker = self::faker();
        $expected = $faker->word();

        self::assertSame($expected, (new BlockDefinition($faker->word(), SampleBlock::class, $expected))->template);
    }

    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    #[Test]
    public function templateInvalid(string $value): void
    {
        self::expectException(\InvalidArgumentException::class);

        new BlockDefinition(self::faker()->word(), SampleBlock::class, $value);
    }

    #[Test]
    public function fromArray(): void
    {
        $expected = new BlockDefinition(
            'sample_block',
            SampleBlock::class,
            'sample/block.html.twig',
        );

        self::assertEquals($expected, BlockDefinition::fromArray([
            'name' => $expected->name,
            'className' => $expected->className,
            'template' => $expected->template,
        ]));
    }

    #[Test]
    public function fromArraynameKeyMustExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'className' => SampleBlock::class,
            'template' => self::faker()->word(),
        ]);
    }

    #[Test]
    public function fromArraynameInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'name' => self::faker()->randomNumber(),
            'className' => SampleBlock::class,
            'template' => self::faker()->word(),
        ]);
    }

    #[Test]
    public function fromArrayClassNameKeyMustExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'name' => SampleBlock::class,
            'template' => self::faker()->word(),
        ]);
    }

    #[Test]
    public function fromArrayClassNameInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'name' => self::faker()->randomNumber(),
            'className' => self::faker()->randomNumber(),
            'template' => self::faker()->word(),
        ]);
    }

    #[Test]
    public function fromArrayTemplateKeyMustExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'name' => self::faker()->word(),
            'className' => SampleBlock::class,
        ]);
    }

    #[Test]
    public function fromArrayTemplateInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'name' => self::faker()->randomNumber(),
            'className' => self::faker()->randomNumber(),
            'template' => self::faker()->randomNumber(),
        ]);
    }
}
