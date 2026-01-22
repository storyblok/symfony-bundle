<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Twig;

use Storyblok\Bundle\Cdn\CdnUrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CdnExtension extends AbstractExtension
{
    public function __construct(
        private CdnUrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cdn_url', $this->urlGenerator->generate(...)),
        ];
    }
}
