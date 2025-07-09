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

namespace Storyblok\Bundle\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\BlockDefinition;
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Util\ValueObjectTrait;

final class ValueObjectTraitTest extends TestCase
{
    use FakerTrait;

    protected function setUp(): void
    {
        // Clear the static registry before each test
        BlockRegistry::$blocks = [];
    }

    protected function tearDown(): void
    {
        // Clear the static registry after each test
        BlockRegistry::$blocks = [];
    }

    #[Test]
    public function blocks(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        // Add a block to the registry
        BlockRegistry::add(new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'));

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block',
                    'description' => 'Test description'
                ]
            ]
        ];

        $result = $class::Blocks($values, 'blocks');

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertInstanceOf(SampleBlock::class, $result[0]);
    }

    #[Test]
    public function blocksReturnsEmptyArrayWhenKeyDoesNotExist(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        $values = [];

        $result = $class::Blocks($values, 'non-existent-key');

        self::assertSame([], $result);
    }

    #[Test]
    public function blocksWithCount(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        // Add a block to the registry
        BlockRegistry::add(new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'));

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 1',
                    'description' => 'Test description 1'
                ],
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 2',
                    'description' => 'Test description 2'
                ]
            ]
        ];

        $result = $class::Blocks($values, 'blocks', count: 2);

        self::assertCount(2, $result);
    }

    #[Test]
    public function blocksWithCountThrowsExceptionWhenCountDoesNotMatch(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block',
                    'description' => 'Test description'
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);

        $class::Blocks($values, 'blocks', count: 2);
    }

    #[Test]
    public function blocksWithMinCount(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        // Add a block to the registry
        BlockRegistry::add(new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'));

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 1',
                    'description' => 'Test description 1'
                ],
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 2',
                    'description' => 'Test description 2'
                ]
            ]
        ];

        $result = $class::Blocks($values, 'blocks', min: 1);

        self::assertCount(2, $result);
    }

    #[Test]
    public function blocksWithMinCountThrowsExceptionWhenBelowMin(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block',
                    'description' => 'Test description'
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);

        $class::Blocks($values, 'blocks', min: 2);
    }

    #[Test]
    public function blocksWithMaxCount(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        // Add a block to the registry
        BlockRegistry::add(new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'));

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block',
                    'description' => 'Test description'
                ]
            ]
        ];

        $result = $class::Blocks($values, 'blocks', max: 2);

        self::assertCount(1, $result);
    }

    #[Test]
    public function blocksWithMaxCountThrowsExceptionWhenAboveMax(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 1',
                    'description' => 'Test description 1'
                ],
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 2',
                    'description' => 'Test description 2'
                ],
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 3',
                    'description' => 'Test description 3'
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);

        $class::Blocks($values, 'blocks', max: 2);
    }

    #[Test]
    public function blocksThrowsExceptionWhenCountUsedWithMinOrMax(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block',
                    'description' => 'Test description'
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You can not use $count with $min or $max.');

        $class::Blocks($values, 'blocks', min: 1, count: 1);
    }

    #[Test]
    public function blocksThrowsExceptionWhenValueIsNotList(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        $values = ['blocks' => 'not-a-list'];

        $this->expectException(\InvalidArgumentException::class);

        $class::Blocks($values, 'blocks');
    }

    #[Test]
    public function blocksThrowsExceptionWhenComponentKeyMissing(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        $values = [
            'blocks' => [
                [
                    'title' => 'Test Block without component',
                    'description' => 'Test description'
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);

        $class::Blocks($values, 'blocks');
    }

    #[Test]
    public function blocksIgnoresBlocksWhenBlockNotFound(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        // Don't add the block to the registry, so it won't be found
        $values = [
            'blocks' => [
                [
                    'component' => 'unknown_block',
                    'title' => 'Unknown Block',
                    'description' => 'Unknown description'
                ]
            ]
        ];

        $result = $class::Blocks($values, 'blocks');

        // Should return empty array since the block was not found and ignored
        self::assertSame([], $result);
    }

    #[Test]
    public function blocksHandlesMixedValidAndInvalidBlocks(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        // Add only one block to the registry
        BlockRegistry::add(new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'));

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Valid Block',
                    'description' => 'Valid description'
                ],
                [
                    'component' => 'unknown_block',
                    'title' => 'Invalid Block',
                    'description' => 'Invalid description'
                ]
            ]
        ];

        $result = $class::Blocks($values, 'blocks');

        // Should only return the valid block, ignoring the invalid one
        self::assertCount(1, $result);
        self::assertInstanceOf(SampleBlock::class, $result[0]);
    }

    #[Test]
    public function blocksMethodIsProtectedAndFinal(): void
    {
        $class = new class() {
            use ValueObjectTrait;
        };
        
        $reflection = new \ReflectionClass($class);
        $method = $reflection->getMethod('Blocks');

        self::assertTrue($method->isProtected());
        self::assertTrue($method->isFinal());
        self::assertTrue($method->isStatic());
    }

    #[Test]
    public function blocksWithMinAndMaxCount(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        // Add a block to the registry
        BlockRegistry::add(new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'));

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 1',
                    'description' => 'Test description 1'
                ],
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block 2',
                    'description' => 'Test description 2'
                ]
            ]
        ];

        $result = $class::Blocks($values, 'blocks', min: 1, max: 3);

        self::assertCount(2, $result);
    }

    #[Test]
    public function blocksWithCountAndMaxThrowsException(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block',
                    'description' => 'Test description'
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You can not use $count with $min or $max.');

        $class::Blocks($values, 'blocks', max: 2, count: 1);
    }

    #[Test]
    public function blocksCreatesInstancesWithCorrectData(): void
    {
        $class = new class() {
            use ValueObjectTrait {
                ValueObjectTrait::Blocks as public;
            }
        };

        // Add a block to the registry
        BlockRegistry::add(new BlockDefinition('sample_block', SampleBlock::class, 'sample/block.html.twig'));

        $values = [
            'blocks' => [
                [
                    'component' => 'sample_block',
                    'title' => 'Test Block',
                    'description' => 'Test description'
                ]
            ]
        ];

        $result = $class::Blocks($values, 'blocks');

        self::assertCount(1, $result);
        /** @var SampleBlock $block */
        $block = $result[0];
        self::assertInstanceOf(SampleBlock::class, $block);
        self::assertSame('Test Block', $block->title);
        self::assertSame('Test description', $block->description);
    }
}
