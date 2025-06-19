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

namespace Storyblok\Bundle\Tests\Util;

use Storyblok\Api\Bridge\Faker\Generator;
use Storyblok\Bundle\Faker\Provider\EditableProvider;

trait FakerTrait
{
    final protected static function faker(string $locale = 'de_DE'): Generator
    {
        static $fakers = [];

        if (!\array_key_exists($locale, $fakers)) {
            $generator = new Generator();
            $generator->addProvider(new EditableProvider($generator));

            $fakers[$locale] = $generator;
        }

        return $fakers[$locale];
    }
}
