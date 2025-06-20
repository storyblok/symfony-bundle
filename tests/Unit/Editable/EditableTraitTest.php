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

namespace Storyblok\Bundle\Tests\Unit\Editable;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Tests\Double\Block\EditableBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class EditableTraitTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function editableMethod(): void
    {
        $expected = self::faker()->editable();

        self::assertSame($expected, (string) (new EditableBlock(['_editable' => $expected]))->editable());
    }

    #[Test]
    public function editableMethodWithNull(): void
    {
        self::assertNull((new EditableBlock(['_editable' => null]))->editable());
    }
}
