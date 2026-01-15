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

namespace Storyblok\Bundle\Tests\Double\Controller;

use Storyblok\Bundle\ContentType\Attribute\AsContentTypeController;
use Storyblok\Bundle\ContentType\ContentTypeInterface;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Symfony\Component\HttpFoundation\Response;

#[AsContentTypeController(contentType: SampleContentType::class)]
#[AsContentTypeController(contentType: SampleContentType::class, slug: '/legal/imprint')]
#[AsContentTypeController(contentType: SampleContentType::class, slug: '/legal/privacy')]
final class MultipleContentTypesController
{
    public const string IMPRINT_SLUG = '/legal/imprint';
    public const string PRIVACY_SLUG = '/legal/privacy';

    public function __invoke(ContentTypeInterface $contentType): Response
    {
        return new Response($contentType::type());
    }
}
