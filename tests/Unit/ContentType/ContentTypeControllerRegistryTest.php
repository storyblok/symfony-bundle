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

namespace Storyblok\Bundle\Tests\Unit\ContentType;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\ContentType\ContentTypeControllerDefinition;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\Exception\ContentTypeControllerNotFoundException;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Double\Controller\SampleController;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class ContentTypeControllerRegistryTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function add(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, self::faker()->word()));

        self::assertCount(1, $collection);
    }

    #[Test]
    public function addWithArray(): void
    {
        $values = ['className' => SampleController::class, 'contentType' => SampleBlock::class, 'type' => self::faker()->word()];

        $collection = new ContentTypeControllerRegistry();
        $collection->add($values);

        self::assertCount(1, $collection);
    }

    #[Test]
    public function get(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition($className = SampleController::class, SampleContentType::class, self::faker()->word()));

        self::assertSame($className, $collection->get($className)->className);
    }

    #[Test]
    public function getThrowsExceptionWhenBlockDefinitionWasNotFound(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, self::faker()->word()));

        self::expectException(ContentTypeControllerNotFoundException::class);
        self::expectExceptionMessage(\sprintf('ContentTypeController "%s" not found.', \stdClass::class));

        $collection->get(\stdClass::class);
    }

    #[Test]
    public function byType(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, $type = self::faker()->word()));

        self::assertSame($type, $collection->byType($type)->type);
    }

    #[Test]
    public function byTypeThrowsExceptionWhenBlockDefinitionWasNotFound(): void
    {
        $faker = self::faker();
        $type = $faker->word();

        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, $faker->word()));

        self::expectException(ContentTypeControllerNotFoundException::class);
        self::expectExceptionMessage(\sprintf('ContentTypeController by type "%s" not found.', $type));

        $collection->byType($type);
    }

    #[Test]
    public function byTypeThrowsWhenDefinitionExistsButHasSlug(): void
    {
        $faker = self::faker();

        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(SampleController::class, $type = SampleContentType::class, $faker->word(), $faker->slug()));

        self::expectException(ContentTypeControllerNotFoundException::class);
        self::expectExceptionMessage(\sprintf('ContentTypeController by type "%s" not found.', $type));

        $collection->byType($type);
    }

    #[Test]
    public function bySlug(): void
    {
        $faker = self::faker();

        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, $faker->word(), $slug = $faker->slug()));

        self::assertSame($slug, $collection->bySlug($slug)->slug);
    }

    #[Test]
    public function bySlugThrowsExceptionWhenBlockDefinitionWasNotFound(): void
    {
        $faker = self::faker();

        $slug = $faker->slug();

        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, $faker->word()));

        self::expectException(ContentTypeControllerNotFoundException::class);
        self::expectExceptionMessage(\sprintf('ContentTypeController by slug "%s" not found.', $slug));

        $collection->bySlug($slug);
    }
}
