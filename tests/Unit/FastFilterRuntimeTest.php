<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\FastFilter\FastFilterRuntime;
use PHPUnit\Framework\TestCase;

final class FastFilterRuntimeTest extends TestCase
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $previousMerconisGlobals = null;

    protected function setUp(): void
    {
        $this->previousMerconisGlobals = $GLOBALS['merconis_globals'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->previousMerconisGlobals === null) {
            unset($GLOBALS['merconis_globals']);
            return;
        }

        $GLOBALS['merconis_globals'] = $this->previousMerconisGlobals;
    }

    public function testFastFilterIsActiveOnlyWhenBaseFilterAndFastFlagAreEnabled(): void
    {
        $GLOBALS['merconis_globals'] = [
            'ls_shop_activateFilter' => '1',
            'ls_shop_useFastFilter' => '1',
        ];

        self::assertTrue(FastFilterRuntime::isActive());
    }

    public function testFastFilterIsInactiveWithoutBaseFilter(): void
    {
        $GLOBALS['merconis_globals'] = [
            'ls_shop_activateFilter' => '',
            'ls_shop_useFastFilter' => '1',
        ];

        self::assertFalse(FastFilterRuntime::isActive());
    }

    public function testFastFilterIsInactiveWithoutFastFlag(): void
    {
        $GLOBALS['merconis_globals'] = [
            'ls_shop_activateFilter' => '1',
            'ls_shop_useFastFilter' => '',
        ];

        self::assertFalse(FastFilterRuntime::isActive());
    }
}
