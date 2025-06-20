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
use function Safe\json_encode;

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
    public function getFunctions(): void
    {
        $functions = (new LiveEditorExtension())->getFunctions();

        self::assertCount(1, $functions);
        self::assertSame('storyblok_js_bridge_scripts', $functions[0]->getName());
    }

    #[Test]
    public function attributes(): void
    {
        $faker = self::faker();
        $loader = new FilesystemLoader();
        $loader->setPaths(__DIR__.'/../../../templates', 'Storyblok');

        $twig = new Environment($loader);

        $block = new EditableBlock([
            '_editable' => $faker->editable(
                uid: $uid = $faker->uuid(),
                id: $id = (string) $faker->numberBetween(1, 1000),
            ),
        ]);

        self::assertSame(
            \sprintf('data-blok-c="%s" data-blok-uid="%d-%s"', htmlspecialchars(json_encode($block->editable()?->toArray())), $id, $uid),
            (new LiveEditorExtension())->attributes($twig, $block),
        );
    }

    #[Test]
    public function includeStoryblokBridgeWithVersionPublished(): void
    {
        $loader = new FilesystemLoader();
        $loader->setPaths(__DIR__.'/../../../templates', 'Storyblok');

        $twig = new Environment($loader);

        self::assertEmpty((new LiveEditorExtension('published'))->includeStoryblokBridge($twig));
    }

    #[Test]
    public function includeStoryblokBridgeWithVersionDraft(): void
    {
        $loader = new FilesystemLoader();
        $loader->setPaths(__DIR__.'/../../../templates', 'Storyblok');

        $twig = new Environment($loader);

        self::assertSame(<<<'HTML'
<script src="https://app.storyblok.com/f/storyblok-v2-latest.js" async onload="initialize()" type="text/javascript"></script>
    <script>
        function initialize() {
            const {StoryblokBridge} = window
            const storyblokInstance = new StoryblokBridge()

            storyblokInstance.on(['published', 'change'], () => {
                window.location.reload()
            })
        }
    </script>
HTML, (new LiveEditorExtension())->includeStoryblokBridge($twig));
    }
}
