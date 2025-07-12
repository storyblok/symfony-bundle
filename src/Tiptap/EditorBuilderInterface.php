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

namespace Storyblok\Bundle\Tiptap;

use Tiptap\Editor;

interface EditorBuilderInterface
{
    /**
     * @param array{
     *     type: 'doc',
     *     content: list<mixed[]>
     * } $values
     */
    public function getEditor(array $values): Editor;
}
