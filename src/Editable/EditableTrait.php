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

namespace Storyblok\Bundle\Editable;

use Storyblok\Bundle\Editable\Domain\Editable;

trait EditableTrait
{
    private ?Editable $editable = null;

    public function editable(): Editable|null
    {
        return $this->editable;
    }
}
