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

    #[DataProvider('provideLengthWarningMessages')]
    public function testFormatLegalGuaranteeImportWarningIncludesRowFieldAndLimit(
        string $code,
        string $field,
        string $limit,
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
            'parameters' => [$limit],
        ]);

        self::assertSame(
            sprintf('line 5, field "%s": %s', $field, $expectedDetail),
            $message
        );
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function provideLengthWarningMessages(): iterable
    {
        yield 'brand' => [
            'garan_disabled_brand_too_long',
            'guaranteeBrand',
            '35',
            'GARAN was not saved because "Brand/Trademark" must not be longer than 35 characters.',
        ];

        yield 'model' => [
            'garan_disabled_model_too_long',
            'guaranteeModelIdentifier',
            '11',
            'GARAN was not saved because "Model identifier" must not be longer than 11 characters.',
        ];
    }
}
