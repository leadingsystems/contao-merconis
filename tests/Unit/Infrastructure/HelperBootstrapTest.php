<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;

final class HelperBootstrapTest extends TestCase
{
    public function testGeneralHelperCanBeAutoloaded(): void
    {
        self::assertTrue(
            class_exists(\Merconis\Core\ls_shop_generalHelper::class),
            'Expected Merconis helper class to be autoloadable.'
        );
    }
}
