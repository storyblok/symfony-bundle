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

namespace Storyblok\Bundle\Tests\Unit\ValueResolver;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\ContentType\ContentTypeStorage;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\ValueResolver\ContentTypeValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class ContentTypeValueResolverTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function resolve(): void
    {
        $faker = self::faker();
        $storage = new ContentTypeStorage();
        $storage->setContentType($expected = new SampleContentType([]));

        $resolver = new ContentTypeValueResolver($storage);

        $request = new Request();
        $request->attributes->set('_storyblok_content_type', $expected::class);

        self::assertSame([$expected], $resolver->resolve($request, new ArgumentMetadata($faker->word(), $expected::class, false, false, null)));
    }

    #[Test]
    public function resolveWithoutRequestAttribute(): void
    {
        $faker = self::faker();
        $resolver = new ContentTypeValueResolver(new ContentTypeStorage());

        self::assertEmpty($resolver->resolve(new Request(), new ArgumentMetadata($faker->word(), $faker->word(), false, false, null)));
    }

    #[Test]
    public function resolveArgumentTypeDoesNotMatchRequestAttribute(): void
    {
        $faker = self::faker();
        $resolver = new ContentTypeValueResolver(new ContentTypeStorage());

        $request = new Request();
        $request->attributes->set('_storyblok_content_type', \stdClass::class);

        self::assertEmpty($resolver->resolve($request, new ArgumentMetadata($faker->word(), $faker->word(), false, false, null)));
    }

    #[Test]
    public function resolveStorageIsEmpty(): void
    {
        $faker = self::faker();
        $resolver = new ContentTypeValueResolver(new ContentTypeStorage());

        $request = new Request();
        $request->attributes->set('_storyblok_content_type', SampleContentType::class);

        self::assertEmpty($resolver->resolve($request, new ArgumentMetadata($faker->word(), SampleContentType::class, false, false, null)));
    }
}
