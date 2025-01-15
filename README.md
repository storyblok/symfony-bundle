# Storyblok Bundle

| Branch   | PHP                                                                                                                                                                          | Code Coverage                                                                                                                                             |
|----------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------|
| `master` | [![PHP](https://github.com/sensiolabs-de/storyblok-bundle/actions/workflows/ci.yaml/badge.svg)](https://github.com/sensiolabs-de/storyblok-bundle/actions/workflows/ci.yaml) | [![codecov](https://codecov.io/gh/sensiolabs-de/storyblok-bundle/graph/badge.svg?token=MFMNCBLNXB)](https://codecov.io/gh/sensiolabs-de/storyblok-bundle) |

A Symfony bundle to integrate the [Storyblok headless CMS](https://www.storyblok.com/) with your Symfony application.

This bundle leverages the [sensiolabs-de/storyblok-api](https://github.com/sensiolabs-de/storyblok-api), a type-safe PHP
SDK for Storyblok. It configures the Storyblok client and provides a Symfony Profiler extension for easier debugging and
monitoring of Storyblok API interactions.

## Installation

To install the bundle run:

```bash
composer require sensiolabs-de/storyblok-api sensiolabs-de/storyblok-bundle
```

## Configuration

### Symfony Flex

If you are using `symfony/flex`, the bundle will be automatically enabled and the configuration files will be added to
your project.

### Manual Configuration

If `symfony/flex` is not available, or you prefer manual setup, follow these steps:

1. **Add the Configuration**
   Add the following configuration to your `config/packages/storyblok.yaml`:

    ```yaml
    storyblok:
      base_uri: '%env(STORYBLOK_API_BASE_URI)%'
      token: '%env(STORYBLOK_API_TOKEN)%'
    ```

    If you want to use the AssetsApi, you can also add the following configuration:

    ```yaml
    storyblok:
      # ...
      assets_token: '%env(STORYBLOK_ASSETS_API_TOKEN)%'
    ```

2. **Set Environment Variables**
   Define the necessary environment variables in your `.env` file:

    ```dotenv
    STORYBLOK_API_BASE_URI=https://api.storyblok.com/v1
    STORYBLOK_API_TOKEN=your_storyblok_api_token
    ```

## Usage

### API Usage

After setting up the bundle, you can use the Storyblok client within your Symfony application to interact with the
Storyblok CMS API.

For detailed usage and examples, please refer to
the [Storyblok API SDK documentation](https://github.com/sensiolabs-de/storyblok-api).

### Versions (`draft` and `published`)

Storyblok allows you to work with two versions of your content: `draft` and `published`. By default, the bundle uses the
`published` version. If you want to use the `draft` version, you can set the `version` parameter in the configuration:

```yaml
storyblok:
    # ...
    version: draft
```

### Webhooks

Storyblok Webhooks allow your Symfony application to react to events like content changes. This bundle provides easy
setup for handling these Webhooks.

#### Configuration

To enable Webhooks, add the following route to your application:

```yaml
# config/routes/storyblok.yaml
storyblok:
    resource: '@StoryblokBundle/config/routes.php'
```

This will make a route available at `/storyblok/webhook` to receive Webhook requests. For more details on how Webhooks
work, check the [Storyblok Webhooks Documentation](https://www.storyblok.com/docs/guide/in-depth/webhooks).

#### Verifying Webhook Signatures (Security)

For security, you can enable the verification of Webhook signatures to ensure that the requests come from Storyblok.
This is done by configuring a `webhook_secret`:

```yaml
# config/packages/storyblok.yaml
storyblok:
    # ...
    webhook_secret: '%env(STORYBLOK_WEBHOOK_SECRET)%'
```

You'll need to set this secret in your `.env` file:

```dotenv
STORYBLOK_WEBHOOK_SECRET=your_webhook_secret
```

Once enabled, the bundle will automatically validate each Webhook request against this secret.

#### Handling Webhook Events

To process Webhooks, implement the `WebhookHandlerInterface`. The bundle automatically registers any classes
implementing this interface as Webhook handlers, no additional service configuration is required.

**Example Webhook Handler**

Here's an example of a Webhook handler that purges a Varnish cache whenever certain events occur (e.g., content
published or deleted):

```php
<?php

namespace App\Webhook;

use Storyblok\Bundle\Webhook\Event;
use Storyblok\Bundle\Webhook\Handler\WebhookHandlerInterface;

final class PurgeVarnishHandler implements WebhookHandlerInterface
{
    public function handle(Event $event, array $payload): void
    {
        // Your custom logic for handling the event
        // Example: purging Varnish cache
    }

    public function supports(Event $event): bool
    {
        // Specify the events your handler supports
        return $event->equalsOneOf([
            Event::StoryPublished,
            Event::StoryUnpublished,
            Event::StoryDeleted,
            Event::StoryMoved,
        ]);
    }

    public static function priority(): int
    {
        // Define the priority for your handler
        return -2000;
    }
}
```

#### Best Practices

- **Handle Only Necessary Events**: Use the `supports` method to filter only the Webhook events your handler should
  process.
- **Prioritize Handlers**: If you have multiple handlers, set the priority appropriately. Handlers with higher
  priority (lower integer value) are executed first.
- **Add Logging**: It's a good idea to log incoming Webhooks and any actions performed, especially for debugging and
  monitoring.

This approach provides a streamlined and secure way to handle Webhooks from Storyblok, allowing your Symfony application
to react to changes effectively. For more details and use cases, you can always refer to
the [Storyblok API SDK documentation](https://github.com/sensiolabs-de/storyblok-api).
