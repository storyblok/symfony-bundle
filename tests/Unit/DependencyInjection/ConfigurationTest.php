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

namespace Storyblok\Bundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\DependencyInjection\Configuration;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;
    use FakerTrait;

    #[Test]
    public function values(): void
    {
        $faker = self::faker();
        $url = $faker->url();
        $token = $faker->uuid();
        $secret = $faker->uuid();
        $version = $faker->randomElement(['draft', 'published']);
        $autoResolve = $faker->boolean();
        $templatePath = $faker->word();
        $maxAge = $faker->numberBetween(3600);
        $public = $faker->boolean();

        self::assertProcessedConfigurationEquals([
            ['base_uri' => $url],
            ['token' => $token],
            ['webhook_secret' => $secret],
            ['version' => $version],
            ['auto_resolve_relations' => $autoResolve],
            ['blocks_template_path' => $templatePath],
            [
                'controller' => [
                    'ascending_redirect_fallback' => false,
                    'cache' => [
                        'public' => $public,
                        'max_age' => $maxAge,
                    ],
                ],
            ],
        ], [
            'base_uri' => $url,
            'token' => $token,
            'webhook_secret' => $secret,
            'version' => $version,
            'auto_resolve_relations' => $autoResolve,
            'blocks_template_path' => $templatePath,
            'controller' => [
                'ascending_redirect_fallback' => false,
                'cache' => [
                    'public' => $public,
                    'must_revalidate' => null,
                    'max_age' => $maxAge,
                    'smax_age' => null,
                ],
            ],
        ]);
    }

    #[Test]
    public function defaults(): void
    {
        $faker = self::faker();
        $url = $faker->url();
        $token = $faker->uuid();

        self::assertProcessedConfigurationEquals([
            ['base_uri' => $url],
            ['token' => $token],
        ], [
            'base_uri' => $url,
            'token' => $token,
            'webhook_secret' => null,
            'version' => 'published',
            'auto_resolve_relations' => false,
            'blocks_template_path' => 'blocks',
            'controller' => [
                'ascending_redirect_fallback' => false,
                'cache' => [
                    'public' => null,
                    'must_revalidate' => null,
                    'max_age' => null,
                    'smax_age' => null,
                ],
            ],
        ]);
    }

    #[Test]
    public function configBaseUriMustExist(): void
    {
        $faker = self::faker();
        $token = $faker->uuid();

        self::assertConfigurationIsInvalid(
            [['token' => $token]],
            'The child config "base_uri" under "storyblok" must be configured.',
        );
    }

    #[Test]
    public function configTokenMustExist(): void
    {
        $faker = self::faker();
        $url = $faker->url();

        self::assertConfigurationIsInvalid(
            [['base_uri' => $url]],
            'The child config "token" under "storyblok" must be configured.',
        );
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
