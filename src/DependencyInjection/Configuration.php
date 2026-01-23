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
    /**
     * @return TreeBuilder<'array'>
     */
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
                ->booleanNode('auto_resolve_relations')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('auto_resolve_links')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('blocks_template_path')
                    ->defaultValue('blocks')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('controller')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('ascending_redirect_fallback')
                            ->defaultFalse()
                            ->info('Will redirect to the parent route if a route can not be matched until the root route. E.g. /blog/2023/10/01 will redirect to /blog/2023/10 and so on until /blog is reached.')
                        ->end()
                        ->arrayNode('cache')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('public')
                                    ->defaultNull()
                                ->end()
                                ->booleanNode('must_revalidate')
                                    ->defaultNull()
                                ->end()
                                ->integerNode('max_age')
                                    ->defaultNull()
                                ->end()
                                ->integerNode('smax_age')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cdn')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifTrue()
                        ->then(static fn (): array => ['enabled' => true])
                    ->end()
                    ->beforeNormalization()
                        ->ifFalse()
                        ->then(static fn (): array => ['enabled' => false])
                    ->end()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('CDN is enabled by default. Set to false to disable.')
                        ->end()
                        ->arrayNode('storage')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('type')
                                    ->values(['filesystem', 'custom'])
                                    ->defaultValue('filesystem')
                                    ->info('Use "filesystem" for built-in storage or "custom" when providing your own CdnFileStorageInterface implementation.')
                                ->end()
                                ->scalarNode('path')
                                    ->defaultValue('%kernel.project_dir%/var/cdn')
                                    ->info('Storage path for the filesystem storage. Only used when type is "filesystem".')
                                ->end()
                            ->end()
                            ->validate()
                                ->ifTrue(static fn (array $v): bool => 'custom' === $v['type'] && '%kernel.project_dir%/var/cdn' !== $v['path'])
                                ->thenInvalid('The "path" option should not be set when using "custom" storage type. Configure your own CdnFileStorageInterface implementation instead.')
                            ->end()
                        ->end()
                        ->arrayNode('cache')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('public')
                                    ->defaultNull()
                                ->end()
                                ->integerNode('max_age')
                                    ->defaultNull()
                                ->end()
                                ->integerNode('smax_age')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
