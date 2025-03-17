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

namespace Storyblok\Bundle\Tests\Unit\Block\Renderer;

use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\BlockCollection;
use Storyblok\Bundle\Block\BlockDefinition;
use Storyblok\Bundle\Block\Renderer\BlockRenderer;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Twig\Environment;

final class BlockRendererTest extends TestCase
{
    use FakerTrait;

    /**
     * @test
     */
    public function render(): void
    {
        $faker = self::faker();

        $expected = $faker->realText();

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->willReturn($expected);

        $collection = new BlockCollection([
            new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'),
        ]);

        $values = [
            'component' => 'sample_block',
            'title' => $faker->sentence(),
            'description' => $faker->realText(),
        ];

        self::assertSame($expected, (new BlockRenderer($twig, $collection))->render($values));
    }

    /**
     * @test
     */
    public function renderWithObject(): void
    {
        $faker = self::faker();

        $expected = $faker->realText();

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->willReturn($expected);

        $collection = new BlockCollection([
            new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'),
        ]);

        $values = new SampleBlock([
            'component' => 'sample_block',
            'title' => $faker->sentence(),
            'description' => $faker->realText(),
        ]);

        self::assertSame($expected, (new BlockRenderer($twig, $collection))->render($values));
    }

    /**
     * @test
     */
    public function renderValuesComponentKeyMustExist(): void
    {
        $faker = self::faker();

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::never())
            ->method('render');

        $collection = new BlockCollection([
            new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'),
        ]);

        $values = [
            'title' => $faker->sentence(),
            'description' => $faker->realText(),
        ];

        self::expectException(\InvalidArgumentException::class);

        (new BlockRenderer($twig, $collection))->render($values);
    }

    /**
     * @test
     */
    public function renderCatchesBlockNotFoundExceptionAndReturnsEmptyString(): void
    {
        $faker = self::faker();

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::never())
            ->method('render');

        $collection = new BlockCollection([
            new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'),
        ]);

        $values = [
            'component' => $faker->word(),
            'title' => $faker->sentence(),
            'description' => $faker->realText(),
        ];

        self::assertSame('', (new BlockRenderer($twig, $collection))->render($values));
    }
}
