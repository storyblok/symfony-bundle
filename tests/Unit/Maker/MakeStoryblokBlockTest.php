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

namespace Storyblok\Bundle\Tests\Unit\Maker;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Maker\MakeStoryblokBlock;
use Storyblok\Bundle\Maker\RemoteComponent;

final class MakeStoryblokBlockTest extends TestCase
{
    #[Test]
    public function collectsWhitelistedChildComponents(): void
    {
        $components = [
            'card_row' => new RemoteComponent('card_row', [
                'cards' => ['type' => 'bloks', 'component_whitelist' => ['card_row_item']],
            ]),
            'card_row_item' => new RemoteComponent('card_row_item', []),
        ];

        self::assertSame(['card_row_item'], MakeStoryblokBlock::childComponentNames('card_row', $components));
    }

    #[Test]
    public function collectsNestedChildrenAndDeduplicates(): void
    {
        $components = [
            'grid' => new RemoteComponent('grid', [
                'items' => ['type' => 'bloks', 'component_whitelist' => ['row']],
            ]),
            'row' => new RemoteComponent('row', [
                'cols' => ['type' => 'bloks', 'component_whitelist' => ['cell', 'row']],
            ]),
            'cell' => new RemoteComponent('cell', []),
        ];

        self::assertSame(['row', 'cell'], MakeStoryblokBlock::childComponentNames('grid', $components));
    }

    #[Test]
    public function ignoresWhitelistEntriesThatDoNotExist(): void
    {
        $components = [
            'card_row' => new RemoteComponent('card_row', [
                'cards' => ['type' => 'bloks', 'component_whitelist' => ['missing']],
            ]),
        ];

        self::assertSame([], MakeStoryblokBlock::childComponentNames('card_row', $components));
    }

    #[Test]
    public function returnsEmptyWhenBloksFieldIsNotRestricted(): void
    {
        $components = [
            'hero' => new RemoteComponent('hero', [
                'body' => ['type' => 'bloks'],
                'title' => ['type' => 'text'],
            ]),
        ];

        self::assertSame([], MakeStoryblokBlock::childComponentNames('hero', $components));
    }
}
