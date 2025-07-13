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

namespace Storyblok\Bundle\Twig;

use Storyblok\Api\Domain\Type\RichText;
use Storyblok\Bundle\Tiptap\EditorBuilderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class RichTextExtension extends AbstractExtension
{
    public function __construct(
        private EditorBuilderInterface $builder,
    ) {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('rich_text', $this->richText(...), ['is_safe' => ['html']]),
        ];
    }

    public function richText(RichText $richText): string
    {
        return $this->builder->getEditor($richText->toArray())->getHTML();
    }
}
