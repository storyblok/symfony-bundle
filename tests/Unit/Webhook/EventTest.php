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

namespace Storyblok\Bundle\Tests\Unit\Webhook;

use OskarStark\Enum\Test\EnumTestCase;
use Storyblok\Bundle\Webhook\Event;

final class EventTest extends EnumTestCase
{
    protected static function getClass(): string
    {
        return Event::class;
    }

    protected static function getNumberOfValues(): int
    {
        return 11;
    }
}
