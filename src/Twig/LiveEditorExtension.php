<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Twig;

use Storyblok\Api\Domain\Type\Editable;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Bundle\Editable\EditableInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class LiveEditorExtension extends AbstractExtension
{
    private Version $version;

    public function __construct(string $version = 'draft')
    {
        $this->version = Version::from($version);
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'storyblok_js_bridge_scripts',
                $this->includeStoryblokBridge(...),
                ['is_safe' => ['html'], 'needs_environment' => true],
            ),
        ];
    }

    public function includeStoryblokBridge(Environment $twig): string
    {
        return $twig->render('extensions/storyblok_bridge.html.twig', [
            'version' => $this->version,
        ]);
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('storyblok_attributes', $this->attributes(...), ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function attributes(Environment $twig, EditableInterface $editable): string
    {
        if (null === $editable->editable() || Version::Published->equals($this->version)) {
            return '';
        }

        return $twig->render('extensions/storyblok_attributes.html.twig', [
            'editable' => $editable->editable(),
        ]);
    }
}
