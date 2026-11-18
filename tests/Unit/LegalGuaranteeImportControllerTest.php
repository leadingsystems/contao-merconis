<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\System;
use Merconis\Core\ls_shop_importController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Container;

require_once __DIR__ . '/../../src/Resources/contao/classes/ls_shop_importController.php';

/**
 * GLL/GARAN-Teil der Produktzeile im Importer (Spec 27, Abschnitte 4 und 10). Ohne
 * Standard-Benutzergruppe gilt nur `never` als "GLL aus"; so liest der Validator keine
 * Gruppe aus der Datenbank. Neue Produkte haben keine gespeicherten Varianten.
 */
final class LegalGuaranteeImportControllerTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalGlobals = [];
    private mixed $originalContainer;
    private ImportLogRecorder $importLog;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['TL_CONFIG', 'TL_LANG'] as $globalName) {
            $this->originalGlobals[$globalName] = $GLOBALS[$globalName] ?? null;
        }

        $this->originalContainer = System::getContainer();

        if (!defined('TL_MERCONIS_IMPORTER')) {
            define('TL_MERCONIS_IMPORTER', 'MERCONIS IMPORTER');
        }

        unset($GLOBALS['TL_CONFIG']['ls_shop_standardGroup']);
        $GLOBALS['TL_LANG']['MSC']['ls_shop']['legalGuaranteeImport'] = [
            'importLogEntry' => 'Zeile <%d>, Feld <%s>: %s',
            'messages' => [
                'gll_invalid_mode' => 'Text gll_invalid_mode <%s>',
                'garan_disabled_gll_required' => 'Text gll_required',
                'garan_disabled_missing_brand' => 'Text missing_brand',
                'garan_disabled_invalid_duration' => 'Text invalid_duration <%s>',
            ],
        ];
        $_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow'] = 7;

        $this->importLog = new ImportLogRecorder();
        $container = new Container();
        $container->set('monolog.logger.contao', $this->importLog);
        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['lsShop']['importFileInfo']['intCurrentlyReadImportFileRow']);

        foreach ($this->originalGlobals as $globalName => $originalValue) {
            if ($originalValue === null) {
                unset($GLOBALS[$globalName]);
            } else {
                $GLOBALS[$globalName] = $originalValue;
            }
        }

        $containerProperty = new ReflectionProperty(System::class, 'objContainer');
        $containerProperty->setValue(null, $this->originalContainer);

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function gllModeCellProvider(): iterable
    {
        yield 'Spalte fehlt' => [[], 'inherit'];
        yield 'leere Zelle' => [['gllMode' => ''], 'inherit'];
        yield 'inherit' => [['gllMode' => 'inherit'], 'inherit'];
        yield 'always' => [['gllMode' => 'always'], 'always'];
        yield 'never' => [['gllMode' => 'never'], 'never'];
    }

    /**
     * @param array<string, mixed> $gllModeCell
     */
    #[DataProvider('gllModeCellProvider')]
    public function testProductRowGetsGllModeToWrite(array $gllModeCell, string $expectedGllMode): void
    {
        $row = $this->applyToProductRow(array_merge($this->createProductRow(), $gllModeCell));

        self::assertIsArray($row);
        self::assertSame($expectedGllMode, $row['gllMode']);
        self::assertSame([], $this->importLog->records);
    }

    public function testEnableGllColumnHasNoEffect(): void
    {
        $rowWithoutGllMode = $this->applyToProductRow(array_merge($this->createProductRow(), ['enableGll' => '']));
        $rowWithNever = $this->applyToProductRow(array_merge(
            $this->createProductRow(),
            ['enableGll' => '1', 'gllMode' => 'never', 'enableGaran' => '1', 'guaranteeDurationMonths' => '36', 'guaranteeBrand' => 'ACME', 'guaranteeModelIdentifier' => 'M-1']
        ));

        self::assertIsArray($rowWithoutGllMode);
        self::assertSame('inherit', $rowWithoutGllMode['gllMode']);
        self::assertNull($rowWithNever);
        self::assertSame(['MERCONIS IMPORTER: Zeile <5>, Feld <gllMode>: Text gll_required'], $this->importLog->records);
    }

    public function testInvalidGllModeIsRejectedWithRowFieldAndText(): void
    {
        self::assertNull($this->applyToProductRow(array_merge($this->createProductRow(), ['gllMode' => 'sometimes'])));
        self::assertSame(
            ['MERCONIS IMPORTER: Zeile <5>, Feld <gllMode>: Text gll_invalid_mode <sometimes>'],
            $this->importLog->records
        );
    }

    #[DataProvider('provideWarningMessages')]
    public function testFormatLegalGuaranteeImportWarningIncludesRowFieldAndText(
        string $code,
        string $field,
        array $parameters,
        string $expectedText
    ): void {
        $method = new \ReflectionMethod(ls_shop_importController::class, 'formatLegalGuaranteeImportWarning');

        $message = $method->invoke($this->createController(), [
            'code' => $code,
            'field' => $field,
            'parameters' => $parameters,
        ]);

        self::assertSame(sprintf('Zeile <5>, Feld <%s>: %s', $field, $expectedText), $message);
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
            'Text missing_brand',
        ];

        yield 'invalid duration' => [
            'garan_disabled_invalid_duration',
            'guaranteeDurationMonths',
            ['24'],
            'Text invalid_duration <24>',
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null
     */
    private function applyToProductRow(array $row): ?array
    {
        $method = new \ReflectionMethod(ls_shop_importController::class, 'applyLegalGuaranteeConfigurationToProductRow');

        return $method->invoke($this->createController(), $row, 0, $row['guaranteeDurationMonths'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function createProductRow(): array
    {
        return [
            'productcode' => 'ART-1',
            'producer' => 'ACME',
            'enableGaran' => '',
            'guaranteeDurationMonths' => '',
            'guaranteeBrand' => '',
            'guaranteeModelIdentifier' => '',
        ];
    }

    private function createController(): ls_shop_importController
    {
        $reflectionClass = new \ReflectionClass(ls_shop_importController::class);
        $controller = $reflectionClass->newInstanceWithoutConstructor();
        $headerFlagProperty = $reflectionClass->getProperty('bln_importFileHasRowForVersionAndSpecialChars');
        $headerFlagProperty->setValue($controller, true);

        return $controller;
    }
}

final class ImportLogRecorder extends AbstractLogger
{
    /**
     * @var list<string>
     */
    public array $records = [];

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = (string) $message;
    }
}
