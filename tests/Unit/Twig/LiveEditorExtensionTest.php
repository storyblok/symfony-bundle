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

namespace Storyblok\Bundle\Tests\Unit\Twig;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Tests\Double\Block\EditableBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Twig\LiveEditorExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class LiveEditorExtensionTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function getFilters(): void
    {
        $filters = (new LiveEditorExtension())->getFilters();

        self::assertCount(1, $filters);
        self::assertSame('storyblok_attributes', $filters[0]->getName());
    }

    #[Test]
    public function attributes(): void
    {
        $faker = self::faker();
        $twig = new Environment(new FilesystemLoader([
            __DIR__.'/../../../templates',
        ]));

        $block = new EditableBlock([
            '_editable' => $faker->editable(
                uid: $uid = $faker->uuid(),
                id: $id = (string) $faker->numberBetween(1, 1000),
            ),
        ]);

        self::assertSame(
            \sprintf('data-blok-c="%s" data-blok-uid="%d-%s"', htmlspecialchars(\json_encode($block->editable()?->toArray())), $id, $uid),
            (new LiveEditorExtension())->attributes($twig, $block),
        );
    }
}
