<?php

declare(strict_types=1);

/**
 * This file is part of sensiolabs-de/storyblok-api-bundle.
 *
 * (c) SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('storyblok');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('base_uri')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('token')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('assets_token')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('webhook_secret')
                    ->defaultNull()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('version')
                    ->defaultValue('published')
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('auto_resolve_stories')
                    ->defaultValue(false)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
