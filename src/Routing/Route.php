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

namespace Storyblok\Bundle\Routing;

enum Route
{
    public const string WEBHOOK = 'storyblok_webhook';
    public const string CONTENT_TYPE = 'storyblok_content_type';
}
