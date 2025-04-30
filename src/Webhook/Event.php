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

namespace Storyblok\Bundle\Webhook;

use OskarStark\Enum\Trait\Comparable;

/**
 * @see https://www.storyblok.com/docs/guide/in-depth/webhooks
 */
enum Event: string
{
    use Comparable;

    // Story
    case StoryPublished = 'story.published';
    case StoryUnpublished = 'story.unpublished';
    case StoryMoved = 'story.moved';
    case StoryDeleted = 'story.deleted';

    // Datasource
    case DatasourceEntriesUpdated = 'datasource.entries_updated';
    case DatasourceEntrySaved = 'datasource.datasource_entry_saved';

    // Asset
    case AssetCreated = 'asset.created';
    case AssetReplaced = 'asset.replaced';
    case AssetDeleted = 'asset.deleted';
    case AssetRestored = 'asset.restored';

    // Workflow
    case WorkflowStageChanged = 'workflow.stage.changed';
}
