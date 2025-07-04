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

use Storyblok\Api\Domain\Type\Asset;
use Storyblok\Api\Domain\Type\MultiLink;
use Storyblok\Api\Domain\Type\RichText;
use Storyblok\Bundle\Block\Attribute\AsBlock;
use Storyblok\Bundle\Util\HelperTrait;

#[AsBlock]
final readonly class BlockUsingHelperTrait
{
    use HelperTrait;

    public string $title;
    public ?string $description;
    public Asset $image;
    public MultiLink $link;

    /**
     * @var list<object>
     */
    public array $blocks;

    public RichText $content;
    public ?object $button;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
        $this->title = self::string($values, 'title');
        $this->description = self::nullOrString($values, 'description');
        $this->image = self::Asset($values, 'image');
        $this->link = self::MultiLink($values, 'link');
        $this->blocks = self::Blocks($values, 'blocks');
        $this->content = self::RichText($values, 'content');
        $this->button = self::nullOrOne($values, 'button', \stdClass::class);
    }
}
