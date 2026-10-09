<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ProductGuaranteeBrandDefaultTest extends TestCase
{
    private mixed $previousDatabaseInstance = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadProductControllerClass();
        $this->previousDatabaseInstance = $this->getDatabaseInstanceProperty()->getValue();
        Input::resetCache();
    }

    protected function tearDown(): void
    {
        foreach ([
            'FORM_SUBMIT',
            'SUBMIT_TYPE',
            'lsShopProductProducer',
            'guaranteeBrand',
        ] as $fieldName) {
            Input::setPost($fieldName, null);
        }

        Input::resetCache();
        $this->getDatabaseInstanceProperty()->setValue(null, $this->previousDatabaseInstance);
        parent::tearDown();
    }

    public function testOnloadCallbackPrefillsBrandFromPostedProducerOnFirstManualSave(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => '',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_product');
        Input::setPost('lsShopProductProducer', 'ACME Corporation');
        Input::setPost('guaranteeBrand', '');

        $controller = $this->createController();
        $controller->applyGuaranteeBrandDefaultForNewProduct($this->createDataContainer(17));

        self::assertSame([[17]], $databaseSpy->selectExecutions);
        self::assertSame([1], $databaseSpy->selectLimits);
        self::assertSame('ACME Corporation', Input::post('guaranteeBrand'));
    }

    public function testOnloadCallbackSkipsAutoSubmitToAvoidSubpalettePrefill(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => '',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_product');
        Input::setPost('SUBMIT_TYPE', 'auto');
        Input::setPost('lsShopProductProducer', 'ACME Corporation');
        Input::setPost('guaranteeBrand', '');

        $controller = $this->createController();
        $controller->applyGuaranteeBrandDefaultForNewProduct($this->createDataContainer(18));

        self::assertSame('', Input::post('guaranteeBrand'));
    }

    public function testOnbeforesubmitCallbackAddsBrandForFirstSaveWithoutPostedBrandField(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => '',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);
        Input::setPost('lsShopProductProducer', 'ACME Corporation');

        $controller = $this->createController();
        $submittedValues = $controller->applyGuaranteeBrandDefaultForNewProduct(
            ['enableGaran' => ''],
            $this->createDataContainer(19)
        );

        self::assertSame('ACME Corporation', $submittedValues['guaranteeBrand']);
    }

    public function testCallbacksPersistBrandInSameRequestForFirstSaveWithGaranEnabled(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => '',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_product');
        Input::setPost('lsShopProductProducer', 'ACME Corporation');
        Input::setPost('guaranteeBrand', '');

        $controller = $this->createController();
        $dataContainer = $this->createDataContainer(23);

        $controller->applyGuaranteeBrandDefaultForNewProduct($dataContainer);

        $submittedValues = $controller->applyGuaranteeBrandDefaultForNewProduct(
            [
                'enableGaran' => '1',
                'guaranteeBrand' => Input::post('guaranteeBrand'),
            ],
            $dataContainer
        );

        self::assertSame('ACME Corporation', Input::post('guaranteeBrand'));
        self::assertSame('ACME Corporation', $submittedValues['guaranteeBrand']);
    }

    public function testCallbacksPersistBrandInSameRequestForFirstSaveWithGaranDisabled(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => '',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_product');
        Input::setPost('lsShopProductProducer', 'ACME Corporation');
        Input::setPost('guaranteeBrand', '');

        $controller = $this->createController();
        $dataContainer = $this->createDataContainer(24);

        $controller->applyGuaranteeBrandDefaultForNewProduct($dataContainer);

        $submittedValues = $controller->applyGuaranteeBrandDefaultForNewProduct(
            ['enableGaran' => ''],
            $dataContainer
        );

        self::assertSame('ACME Corporation', Input::post('guaranteeBrand'));
        self::assertSame('ACME Corporation', $submittedValues['guaranteeBrand']);
    }

    public function testCallbackSkipsFirstSaveWithoutProducer(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => '',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_product');
        Input::setPost('lsShopProductProducer', '');
        Input::setPost('guaranteeBrand', '');

        $controller = $this->createController();
        $submittedValues = $controller->applyGuaranteeBrandDefaultForNewProduct(
            ['enableGaran' => ''],
            $this->createDataContainer(20)
        );

        self::assertSame('', Input::post('guaranteeBrand'));
        self::assertArrayNotHasKey('guaranteeBrand', $submittedValues);
    }

    public function testCallbackKeepsExistingBrandOnFirstSave(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => '',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_product');
        Input::setPost('lsShopProductProducer', 'ACME Corporation');
        Input::setPost('guaranteeBrand', 'Shop Brand');

        $controller = $this->createController();
        $submittedValues = $controller->applyGuaranteeBrandDefaultForNewProduct(
            ['guaranteeBrand' => 'Shop Brand'],
            $this->createDataContainer(21)
        );

        $controller->applyGuaranteeBrandDefaultForNewProduct($this->createDataContainer(21));

        self::assertSame('Shop Brand', $submittedValues['guaranteeBrand']);
        self::assertSame('Shop Brand', Input::post('guaranteeBrand'));
    }

    public function testCallbackSkipsSavedProductToAvoidLaterProducerOverwrite(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '1234567890',
            'lsShopProductProducer' => 'Original Producer',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);
        Input::setPost('FORM_SUBMIT', 'tl_ls_shop_product');
        Input::setPost('lsShopProductProducer', 'Changed Producer');
        Input::setPost('guaranteeBrand', '');

        $controller = $this->createController();
        $submittedValues = $controller->applyGuaranteeBrandDefaultForNewProduct(
            ['enableGaran' => '1'],
            $this->createDataContainer(22)
        );
        $controller->applyGuaranteeBrandDefaultForNewProduct($this->createDataContainer(22));

        self::assertArrayNotHasKey('guaranteeBrand', $submittedValues);
        self::assertSame('', Input::post('guaranteeBrand'));
    }

    private function createController(): object
    {
        return new class extends \Merconis\Core\tl_ls_shop_product_controller {
            public function __construct()
            {
            }
        };
    }

    private function getDatabaseInstanceProperty(): ReflectionProperty
    {
        $databaseInstanceProperty = new ReflectionProperty(Database::class, 'objInstance');
        $databaseInstanceProperty->setAccessible(true);

        return $databaseInstanceProperty;
    }

    private function createDataContainer(int $id): DataContainer
    {
        return new class($id) extends DataContainer {
            public function __construct(int $id)
            {
                $this->id = $id;
            }

            public function getPalette()
            {
                return '';
            }

            protected function save($varValue)
            {
                return null;
            }
        };
    }

    private function loadProductControllerClass(): void
    {
        if (class_exists(\Merconis\Core\tl_ls_shop_product_controller::class, false)) {
            return;
        }

        $loader = new class {
            public function getTemplateGroup(string $prefix): array
            {
                return [];
            }

            public function load(string $filePath): void
            {
                require_once $filePath;
            }
        };

        $loader->load(__DIR__ . '/../../src/Resources/contao/dca/tl_ls_shop_product.php');
    }
}

final class ProductGuaranteeBrandDefaultDatabaseSpy extends Database
{
    public array $selectExecutions = [];
    public array $selectLimits = [];

    /**
     * @param array<string, mixed> $productRow
     */
    public function __construct(private array $productRow)
    {
    }

    public function prepare($strQuery)
    {
        return new ProductGuaranteeBrandDefaultStatementSpy($this, (string) $strQuery);
    }

    public function recordLimit(int $limit): void
    {
        $this->selectLimits[] = $limit;
    }

    /**
     * @param array<int, mixed> $params
     */
    public function executePreparedStatement(string $query, array $params): object
    {
        if (str_contains($query, 'SELECT      `tstamp`')) {
            $this->selectExecutions[] = $params;

            return new ProductGuaranteeBrandDefaultResultSpy($this->productRow);
        }

        throw new \RuntimeException('Unbekannte Query im Database-Spy.');
    }
}

final class ProductGuaranteeBrandDefaultStatementSpy
{
    public function __construct(
        private ProductGuaranteeBrandDefaultDatabaseSpy $databaseSpy,
        private string $query,
    ) {
    }

    public function limit(int $limit): self
    {
        $this->databaseSpy->recordLimit($limit);

        return $this;
    }

    public function execute(...$params): object
    {
        return $this->databaseSpy->executePreparedStatement($this->query, $params);
    }
}

final class ProductGuaranteeBrandDefaultResultSpy
{
    public int $numRows = 1;
    public mixed $tstamp = null;
    public mixed $lsShopProductProducer = null;
    public mixed $guaranteeBrand = null;

    /**
     * @param array<string, mixed> $row
     */
    public function __construct(private array $row)
    {
        foreach ($row as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function row(): array
    {
        return $this->row;
    }
}
