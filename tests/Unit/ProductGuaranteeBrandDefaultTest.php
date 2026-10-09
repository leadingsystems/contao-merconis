<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\Database;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

final class ProductGuaranteeBrandDefaultTest extends TestCase
{
    private mixed $previousDatabaseInstance = null;
    private array $previousPost = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadProductControllerClass();
        $this->previousDatabaseInstance = $this->getDatabaseInstanceProperty()->getValue();
        $this->previousPost = $_POST ?? [];
    }

    protected function tearDown(): void
    {
        $_POST = $this->previousPost;
        $this->getDatabaseInstanceProperty()->setValue(null, $this->previousDatabaseInstance);
        parent::tearDown();
    }

    public function testApplyGuaranteeBrandDefaultPersistsProducerForUnsavedProduct(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => 'ACME Corporation',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);

        $controller = $this->createController();
        $controller->applyGuaranteeBrandDefaultForNewProduct(17);

        self::assertSame([[17]], $databaseSpy->selectExecutions);
        self::assertSame([1, 1], $databaseSpy->selectLimits);
        self::assertSame([['ACME Corporation', 17]], $databaseSpy->updateExecutions);
    }

    public function testApplyGuaranteeBrandDefaultSkipsUnsavedProductWithoutProducer(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => '',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);

        $controller = $this->createController();
        $controller->applyGuaranteeBrandDefaultForNewProduct(18);

        self::assertSame([], $databaseSpy->updateExecutions);
    }

    public function testApplyGuaranteeBrandDefaultKeepsExistingBrandOnUnsavedProduct(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '0',
            'lsShopProductProducer' => 'ACME Corporation',
            'guaranteeBrand' => 'Shop Brand',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);

        $controller = $this->createController();
        $controller->applyGuaranteeBrandDefaultForNewProduct(19);

        self::assertSame([], $databaseSpy->updateExecutions);
    }

    public function testApplyGuaranteeBrandDefaultSkipsSavedProductToAvoidLaterProducerOverwrite(): void
    {
        $databaseSpy = new ProductGuaranteeBrandDefaultDatabaseSpy([
            'tstamp' => '1234567890',
            'lsShopProductProducer' => 'Changed Producer',
            'guaranteeBrand' => '',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);

        $controller = $this->createController();
        $controller->applyGuaranteeBrandDefaultForNewProduct(20);

        self::assertSame([], $databaseSpy->updateExecutions);
    }

    public function testBuildProductValidationInputDataPrefillsBrandForUnsavedProductFromPostedProducer(): void
    {
        $_POST['lsShopProductProducer'] = 'ACME Corporation';
        $_POST['guaranteeBrand'] = '';

        $controller = $this->createController();
        $result = $this->invokeBuildProductValidationInputData(
            $controller,
            [
                'tstamp' => '0',
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'Model 42',
                'lsShopProductProducer' => '',
            ],
        );

        self::assertSame('ACME Corporation', $result['guaranteeBrand']);
    }

    public function testBuildProductValidationInputDataDoesNotPrefillBrandForSavedProduct(): void
    {
        $_POST['lsShopProductProducer'] = 'Changed Producer';
        $_POST['guaranteeBrand'] = '';

        $controller = $this->createController();
        $result = $this->invokeBuildProductValidationInputData(
            $controller,
            [
                'tstamp' => '1234567890',
                'enableGll' => '1',
                'enableGaran' => '1',
                'guaranteeDurationYears' => '',
                'guaranteeBrand' => '',
                'guaranteeModelIdentifier' => 'Model 42',
                'lsShopProductProducer' => 'Original Producer',
            ],
        );

        self::assertSame('', $result['guaranteeBrand']);
        self::assertSame('Changed Producer', $result['lsShopProductProducer']);
    }

    private function createController(): object
    {
        return new class extends \Merconis\Core\tl_ls_shop_product_controller {
            public function __construct()
            {
            }
        };
    }

    /**
     * @param array<string, mixed> $currentData
     *
     * @return array<string, mixed>
     */
    private function invokeBuildProductValidationInputData(object $controller, array $currentData): array
    {
        $method = new ReflectionMethod(\Merconis\Core\tl_ls_shop_product_controller::class, 'buildProductValidationInputData');
        $method->setAccessible(true);

        return $method->invoke($controller, $currentData);
    }

    private function getDatabaseInstanceProperty(): ReflectionProperty
    {
        $databaseInstanceProperty = new ReflectionProperty(Database::class, 'objInstance');
        $databaseInstanceProperty->setAccessible(true);

        return $databaseInstanceProperty;
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
    public array $updateExecutions = [];

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

        if (str_contains($query, 'UPDATE      `tl_ls_shop_product`')) {
            $this->updateExecutions[] = $params;

            return new \stdClass();
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
