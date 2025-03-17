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

use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\BlockDefinition;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class BlockDefinitionTest extends TestCase
{
    use FakerTrait;

    /**
     * @test
     */
    public function technicalName(): void
    {
        $faker = self::faker();
        $expected = $faker->word();

        self::assertSame($expected, (new BlockDefinition($expected, SampleBlock::class, 'sample/block.html.twig'))->technicalName);
    }

    /**
     * @test
     *
     * @dataProvider \Ergebnis\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\DataProvider\StringProvider::empty()
     */
    public function technicalNameInvalid(string $value): void
    {
        self::expectException(\InvalidArgumentException::class);

        new BlockDefinition($value, SampleBlock::class, 'sample/block.html.twig');
    }

    /**
     * @test
     */
    public function className(): void
    {
        $faker = self::faker();
        $expected = SampleBlock::class;

        self::assertSame($expected, (new BlockDefinition($faker->word(), $expected, 'sample/block.html.twig'))->className);
    }

    /**
     * @test
     *
     * @dataProvider \Ergebnis\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\DataProvider\StringProvider::empty()
     */
    public function classNameInvalid(string $value): void
    {
        self::expectException(\InvalidArgumentException::class);

        new BlockDefinition(self::faker()->word(), $value, 'sample/block.html.twig');
    }

    /**
     * @test
     */
    public function classNameClassMustExist(): void
    {
        $faker = self::faker();
        self::expectException(\InvalidArgumentException::class);

        new BlockDefinition($faker->word(), $faker->word(), 'sample/block.html.twig');
    }

    /**
     * @test
     */
    public function template(): void
    {
        $faker = self::faker();
        $expected = $faker->word();

        self::assertSame($expected, (new BlockDefinition($faker->word(), SampleBlock::class, $expected))->template);
    }

    /**
     * @test
     *
     * @dataProvider \Ergebnis\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\DataProvider\StringProvider::empty()
     */
    public function templateInvalid(string $value): void
    {
        self::expectException(\InvalidArgumentException::class);

        new BlockDefinition(self::faker()->word(), SampleBlock::class, $value);
    }

    /**
     * @test
     */
    public function fromArray(): void
    {
        $expected = new BlockDefinition(
            'sample_block',
            SampleBlock::class,
            'sample/block.html.twig',
        );

        self::assertEquals($expected, BlockDefinition::fromArray([
            'technicalName' => $expected->technicalName,
            'className' => $expected->className,
            'template' => $expected->template,
        ]));
    }

    /**
     * @test
     */
    public function fromArrayTechnicalNameKeyMustExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'className' => SampleBlock::class,
            'template' => self::faker()->word(),
        ]);
    }

    /**
     * @test
     */
    public function fromArrayTechnicalNameInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'technicalName' => self::faker()->randomNumber(),
            'className' => SampleBlock::class,
            'template' => self::faker()->word(),
        ]);
    }

    /**
     * @test
     */
    public function fromArrayClassNameKeyMustExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'technicalName' => SampleBlock::class,
            'template' => self::faker()->word(),
        ]);
    }

    /**
     * @test
     */
    public function fromArrayClassNameInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'technicalName' => self::faker()->randomNumber(),
            'className' => self::faker()->randomNumber(),
            'template' => self::faker()->word(),
        ]);
    }

    /**
     * @test
     */
    public function fromArrayTemplateKeyMustExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'technicalName' => self::faker()->word(),
            'className' => SampleBlock::class,
        ]);
    }

    /**
     * @test
     */
    public function fromArrayTemplateInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);

        BlockDefinition::fromArray([
            'technicalName' => self::faker()->randomNumber(),
            'className' => self::faker()->randomNumber(),
            'template' => self::faker()->randomNumber(),
        ]);
    }
}
