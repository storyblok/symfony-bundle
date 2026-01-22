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

namespace Storyblok\Bundle\Tests\Unit\Cdn\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Cdn\Domain\CdnFileId;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 * @author Stiven Llupa <stiven.llupa@gmail.com>
 */
final class CdnFileIdTest extends TestCase
{
    #[Test]
    public function validId(): void
    {
        $id = new CdnFileId('ef7436441c4defbf');

        self::assertSame('ef7436441c4defbf', $id->value);
    }

    #[Test]
    public function generate(): void
    {
        $url = 'https://a.storyblok.com/f/12345/image.jpg';
        $id = CdnFileId::generate($url);

        self::assertSame(16, \strlen($id->value));
        self::assertMatchesRegularExpression('/[a-f0-9]{16}/', $id->value);
    }

    #[Test]
    public function generateReturnsSameIdForSameUrl(): void
    {
        $url = 'https://a.storyblok.com/f/12345/image.jpg';

        $id1 = CdnFileId::generate($url);
        $id2 = CdnFileId::generate($url);

        self::assertSame($id1->value, $id2->value);
    }

    #[Test]
    public function generateReturnsDifferentIdForDifferentUrl(): void
    {
        $id1 = CdnFileId::generate('https://a.storyblok.com/f/12345/image1.jpg');
        $id2 = CdnFileId::generate('https://a.storyblok.com/f/12345/image2.jpg');

        self::assertNotSame($id1->value, $id2->value);
    }

    #[Test]
    public function throwsExceptionForEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CdnFileId('');
    }

    #[Test]
    public function throwsExceptionForWhitespaceOnly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CdnFileId('   ');
    }

    #[Test]
    public function throwsExceptionForInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CdnFileId('invalid-id');
    }
}
