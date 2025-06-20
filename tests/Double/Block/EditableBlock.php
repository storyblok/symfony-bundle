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

namespace Storyblok\Bundle\Tests\Double\Block;

use Storyblok\Api\Domain\Type\Editable;
use Storyblok\Bundle\Block\Attribute\AsBlock;
use Storyblok\Bundle\Editable\EditableInterface;
use Storyblok\Bundle\Editable\EditableTrait;
use Webmozart\Assert\Assert;

#[AsBlock]
final readonly class EditableBlock implements EditableInterface
{
    use EditableTrait;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
        Assert::keyExists($values, '_editable');
        $this->editable = null !== $values['_editable'] ? new Editable($values['_editable']) : null;
    }
}
