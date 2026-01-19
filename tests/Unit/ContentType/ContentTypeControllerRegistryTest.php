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

namespace Storyblok\Bundle\Tests\Unit\ContentType;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\ContentType\ContentTypeControllerDefinition;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\Exception\ContentTypeControllerNotFoundException;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Double\ContentType\AnotherContentType;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Double\Controller\MultipleContentTypesController;
use Storyblok\Bundle\Tests\Double\Controller\MultipleContentTypesDefaultController;
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
        $values = [
            'className' => SampleController::class,
            'contentType' => SampleBlock::class,
            'type' => self::faker()->word(),
            'resolveRelations' => '',
            'resolveLinks' => ['type' => null, 'level' => 1],
        ];

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

    #[Test]
    public function all(): void
    {
        $faker = self::faker();

        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, $faker->word(), $slug = $faker->slug()));

        self::assertCount(1, $collection->all());
    }

    #[Test]
    public function addMultipleSlugsWithSameControllerAndContentType(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type'));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type', MultipleContentTypesController::IMPRINT_SLUG));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type', MultipleContentTypesController::PRIVACY_SLUG));

        self::assertCount(3, $collection);
    }

    #[Test]
    public function getReturnsFirstDefinitionForControllerWithMultipleSlugs(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add($first = new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type'));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type', MultipleContentTypesController::IMPRINT_SLUG));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type', MultipleContentTypesController::PRIVACY_SLUG));

        self::assertSame($first, $collection->get(MultipleContentTypesController::class));
    }

    #[Test]
    public function bySlugReturnsCorrectDefinitionForControllerWithMultipleSlugs(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type'));
        $collection->add($imprint = new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type', MultipleContentTypesController::IMPRINT_SLUG));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type', MultipleContentTypesController::PRIVACY_SLUG));

        self::assertSame($imprint, $collection->bySlug(MultipleContentTypesController::IMPRINT_SLUG));
    }

    #[Test]
    public function byTypeReturnsCatchAllDefinitionForControllerWithMultipleSlugs(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add($catchAll = new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type'));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type', MultipleContentTypesController::IMPRINT_SLUG));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesController::class, SampleContentType::class, 'sample_content_type', MultipleContentTypesController::PRIVACY_SLUG));

        self::assertSame($catchAll, $collection->byType('sample_content_type'));
    }

    #[Test]
    public function addMultipleContentTypesWithSameController(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesDefaultController::class, SampleContentType::class, 'sample_content_type'));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesDefaultController::class, AnotherContentType::class, 'another_content_type'));

        self::assertCount(2, $collection);
    }

    #[Test]
    public function byTypeReturnsCorrectDefinitionForControllerWithMultipleContentTypes(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesDefaultController::class, SampleContentType::class, 'sample_content_type'));
        $collection->add($another = new ContentTypeControllerDefinition(MultipleContentTypesDefaultController::class, AnotherContentType::class, 'another_content_type'));

        self::assertSame($another, $collection->byType('another_content_type'));
    }

    #[Test]
    public function getReturnsFirstDefinitionForControllerWithMultipleContentTypes(): void
    {
        $collection = new ContentTypeControllerRegistry();
        $collection->add($first = new ContentTypeControllerDefinition(MultipleContentTypesDefaultController::class, SampleContentType::class, 'sample_content_type'));
        $collection->add(new ContentTypeControllerDefinition(MultipleContentTypesDefaultController::class, AnotherContentType::class, 'another_content_type'));

        self::assertSame($first, $collection->get(MultipleContentTypesDefaultController::class));
    }
}
