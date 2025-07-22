<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Storyblok\Api\DatasourceEntriesApi;
use Storyblok\Api\DatasourceEntriesApiInterface;
use Storyblok\Api\LinksApi;
use Storyblok\Api\LinksApiInterface;
use Storyblok\Api\StoriesApi;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Api\StoryblokClient;
use Storyblok\Api\StoryblokClientInterface;
use Storyblok\Api\TagsApi;
use Storyblok\Api\TagsApiInterface;
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Block\Renderer\BlockRenderer;
use Storyblok\Bundle\Block\Renderer\RendererInterface;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\ContentTypeRegistry;
use Storyblok\Bundle\ContentType\ContentTypeRegistryInterface;
use Storyblok\Bundle\ContentType\ContentTypeStorage;
use Storyblok\Bundle\ContentType\ContentTypeStorageInterface;
use Storyblok\Bundle\ContentType\Listener\GlobalCachingListener;
use Storyblok\Bundle\ContentType\Listener\ResolveControllerListener;
use Storyblok\Bundle\ContentType\Listener\StoryNotFoundExceptionListener;
use Storyblok\Bundle\Controller\WebhookController;
use Storyblok\Bundle\DataCollector\StoryblokCollector;
use Storyblok\Bundle\Listener\UpdateProfilerListener;
use Storyblok\Bundle\Maker\MakeStoryblokBlock;
use Storyblok\Bundle\Tiptap\DefaultEditorBuilder;
use Storyblok\Bundle\Tiptap\EditorBuilderInterface;
use Storyblok\Bundle\Twig\BlockExtension;
use Storyblok\Bundle\Twig\LiveEditorExtension;
use Storyblok\Bundle\Twig\RichTextExtension;
use Storyblok\Bundle\ValueResolver\ContentTypeValueResolver;
use Storyblok\Bundle\Webhook\WebhookEventHandlerChain;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpKernel\KernelEvents;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()

        ->set(WebhookEventHandlerChain::class)

        ->set(WebhookController::class)
            ->tag('controller.service_arguments')

        ->set('storyblok.http_client')
            ->class(HttpClient::class)
            ->factory([HttpClient::class, 'create'])

        ->set('storyblok.scoped_http_client')
            ->class(ScopingHttpClient::class)
            ->factory([ScopingHttpClient::class, 'forBaseUri'])
            ->args([
                '$client' => service('storyblok.http_client'),
                '$baseUri' => param('storyblok_api.base_uri'),
                '$defaultOptions' => [
                    'query' => [
                        'token' => param('storyblok_api.token'),
                    ],
                ],
            ])

        ->set(StoryblokClient::class)
            ->args([
                '$baseUri' => param('storyblok_api.base_uri'),
                '$token' => param('storyblok_api.token'),
            ])
            ->call('withHttpClient', [service('storyblok.scoped_http_client')])
            ->alias(StoryblokClientInterface::class, StoryblokClient::class)

        ->set(DatasourceEntriesApi::class)
            ->alias(DatasourceEntriesApiInterface::class, DatasourceEntriesApi::class)

        ->set(StoriesApi::class)
            ->args([
                '$client' => service(StoryblokClient::class),
                '$version' => param('storyblok_api.version'),
            ])
            ->alias(StoriesApiInterface::class, StoriesApi::class)

        ->set(LinksApi::class)
            ->args([
                '$client' => service(StoryblokClient::class),
                '$version' => param('storyblok_api.version'),
            ])
            ->alias(LinksApiInterface::class, LinksApi::class)

        ->set(TagsApi::class)
            ->alias(TagsApiInterface::class, TagsApi::class)

        ->set(StoryblokCollector::class)
            ->args([
                '$client' => service('storyblok.http_client'),
            ])
            ->tag('data_collector', [
                'priority' => 255,
            ])

        ->set(UpdateProfilerListener::class)
            ->tag('kernel.event_listener', [
                'event' => KernelEvents::RESPONSE,
                'method' => 'onKernelResponse',
                'priority' => -255,
            ])

        ->set(ResolveControllerListener::class)
            ->args([
                '$container' => tagged_locator('storyblok.content_type.controller'),
                '$version' => param('storyblok_api.version'),
            ])
            ->tag('kernel.event_listener', [
                'priority' => 0,
            ])

        ->set(StoryNotFoundExceptionListener::class)
            ->args([
                '$version' => param('storyblok_api.version'),
            ])
            ->tag('kernel.event_listener', [
                'priority' => 0,
            ])

        ->set(GlobalCachingListener::class)
            ->args([
                '$storage' => service(ContentTypeStorageInterface::class),
                '$public' => abstract_arg('public cache directive'),
                '$mustRevalidate' => abstract_arg('must-revalidate cache directive'),
                '$maxAge' => abstract_arg('max-age cache directive'),
                '$smaxAge' => abstract_arg('smaxage cache directive'),
            ])
            ->tag('kernel.event_listener', [
                'priority' => -255,
            ])

        ->set(ContentTypeValueResolver::class)
            ->tag('controller.argument_value_resolver', [
                'priority' => 150,
            ])

        ->set(ContentTypeStorage::class)
            ->alias(ContentTypeStorageInterface::class, ContentTypeStorage::class)

        ->set(BlockRenderer::class)
            ->alias(RendererInterface::class, BlockRenderer::class)

        ->set(DefaultEditorBuilder::class)
            ->alias(EditorBuilderInterface::class, DefaultEditorBuilder::class)

        ->set(BlockRegistry::class)

        ->set(BlockExtension::class)
            ->tag('twig.extension')

        ->set(RichTextExtension::class)
            ->tag('twig.extension')

        ->set(LiveEditorExtension::class)
            ->args([
                '$version' => param('storyblok_api.version'),
            ])
            ->tag('twig.extension')

        ->set(ContentTypeControllerRegistry::class)

        ->set(ContentTypeRegistry::class)
            ->alias(ContentTypeRegistryInterface::class, ContentTypeRegistry::class)

        ->set(MakeStoryblokBlock::class)
            ->tag('maker.command')
    ;
};
