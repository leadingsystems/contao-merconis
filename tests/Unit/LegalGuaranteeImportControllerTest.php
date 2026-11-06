<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Resources/contao/classes/ls_shop_importController.php';

final class LegalGuaranteeImportControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow']);

        parent::tearDown();
    }

    #[DataProvider('provideWarningMessages')]
    public function testFormatLegalGuaranteeImportWarningIncludesRowFieldAndDetail(
        string $code,
        string $field,
        array $parameters,
        string $expectedDetail
    ): void {
        $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] = 7;
        $reflectionClass = new \ReflectionClass(\Merconis\Core\ls_shop_importController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();
        $headerFlagProperty = $reflectionClass->getProperty('bln_importFileHasRowForVersionAndSpecialChars');
        $headerFlagProperty->setAccessible(true);
        $headerFlagProperty->setValue($controller, true);
        $method = new \ReflectionMethod($controller, 'formatLegalGuaranteeImportWarning');
        $method->setAccessible(true);

        $message = $method->invoke($controller, [
            'code' => $code,
            'field' => $field,
            'parameters' => $parameters,
        ]);

        self::assertSame(
            sprintf('line 5, field "%s": %s', $field, $expectedDetail),
            $message
        );
    }

    /**
     * @return iterable<string, array{string, string, array<int, string>, string}>
     */
    public static function provideWarningMessages(): iterable
    {
        yield 'missing brand' => [
            'garan_disabled_missing_brand',
            'guaranteeBrand',
            [],
            'GARAN was not saved because "Brand/Trademark" is missing.',
        ];

        yield 'invalid duration' => [
            'garan_disabled_invalid_duration',
            'guaranteeDurationYears',
            ['24'],
            'GARAN was not saved because "24" is not an allowed guarantee duration. Use whole months from 30 to 360 in steps of 6.',
        ];
    }
}
