<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\DataContainer;
use Contao\Database;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class MessageModelOncreateWithdrawalDefaultsTest extends TestCase
{
    private mixed $previousDatabaseInstance = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMessageModelControllerClass();
        $this->previousDatabaseInstance = $this->getDatabaseInstanceProperty()->getValue();
    }

    protected function tearDown(): void
    {
        $this->getDatabaseInstanceProperty()->setValue(null, $this->previousDatabaseInstance);
        parent::tearDown();
    }

    public function testOncreateWithdrawalDefaultsPersistsDefaultsForWithdrawalConfirmation(): void
    {
        $databaseSpy = new MessageModelOncreateDatabaseSpy([
            42 => 'asWithdrawalConfirmation',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);

        $controller = $this->createController();
        $controller->oncreateWithdrawalDefaults(
            'tl_ls_shop_message_model',
            77,
            ['pid' => 42],
            $this->createDataContainer()
        );

        self::assertSame([[42]], $databaseSpy->selectExecutions);
        self::assertSame([1], $databaseSpy->selectLimits);
        self::assertSame(
            [['withdrawalData', 'withdrawalData', 77]],
            $databaseSpy->updateExecutions
        );
    }

    public function testOncreateWithdrawalDefaultsPersistsDefaultsForWithdrawalNotice(): void
    {
        $databaseSpy = new MessageModelOncreateDatabaseSpy([
            84 => 'asWithdrawalNotice',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);

        $controller = $this->createController();
        $dataContainer = $this->createDataContainer();
        $dataContainer->activeRecord = (object) ['pid' => 84];

        $controller->oncreateWithdrawalDefaults(
            'tl_ls_shop_message_model',
            88,
            [],
            $dataContainer
        );

        self::assertSame([[84]], $databaseSpy->selectExecutions);
        self::assertSame(
            [['withdrawalData', 'withdrawalData', 88]],
            $databaseSpy->updateExecutions
        );
    }

    public function testOncreateWithdrawalDefaultsSkipsNonWithdrawalTypes(): void
    {
        $databaseSpy = new MessageModelOncreateDatabaseSpy([
            21 => 'asOrderConfirmation',
        ]);
        $this->getDatabaseInstanceProperty()->setValue(null, $databaseSpy);

        $controller = $this->createController();
        $dataContainer = $this->createDataContainer();
        $dataContainer->pid = 21;

        $controller->oncreateWithdrawalDefaults(
            'tl_ls_shop_message_model',
            91,
            [],
            $dataContainer
        );

        self::assertSame([[21]], $databaseSpy->selectExecutions);
        self::assertSame([], $databaseSpy->updateExecutions);
    }

    private function createController(): object
    {
        return new class extends \Merconis\Core\tl_ls_shop_message_model_controller {
            public function __construct()
            {
            }
        };
    }

    private function createDataContainer(): DataContainer
    {
        return new class extends DataContainer {
            public function __construct()
            {
            }

            public function getPalette()
            {
                return '';
            }

            protected function save($varValue)
            {
                return $varValue;
            }
        };
    }

    private function getDatabaseInstanceProperty(): ReflectionProperty
    {
        $databaseInstanceProperty = new ReflectionProperty(Database::class, 'objInstance');
        $databaseInstanceProperty->setAccessible(true);

        return $databaseInstanceProperty;
    }

    private function loadMessageModelControllerClass(): void
    {
        if (class_exists(\Merconis\Core\tl_ls_shop_message_model_controller::class, false)) {
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

        $loader->load(__DIR__ . '/../../src/Resources/contao/dca/tl_ls_shop_message_model.php');
    }
}

final class MessageModelOncreateDatabaseSpy extends Database
{
    public array $selectExecutions = [];
    public array $selectLimits = [];
    public array $updateExecutions = [];

    /**
     * @param array<int, string> $sendWhenByMessageTypeId
     */
    public function __construct(private array $sendWhenByMessageTypeId)
    {
    }

    public function prepare($strQuery)
    {
        return new MessageModelOncreateStatementSpy($this, (string) $strQuery);
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
        if (str_contains($query, 'tl_ls_shop_message_type')) {
            $this->selectExecutions[] = $params;
            $messageTypeId = (int) ($params[0] ?? 0);
            $sendWhen = $this->sendWhenByMessageTypeId[$messageTypeId] ?? null;

            return new MessageModelOncreateResultSpy($sendWhen);
        }

        if (str_contains($query, 'UPDATE      `tl_ls_shop_message_model`')) {
            $this->updateExecutions[] = $params;

            return new \stdClass();
        }

        throw new \RuntimeException('Unbekannte Query im Database-Spy.');
    }
}

final class MessageModelOncreateStatementSpy
{
    public function __construct(
        private MessageModelOncreateDatabaseSpy $databaseSpy,
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

final class MessageModelOncreateResultSpy
{
    public int $numRows;
    public ?string $sendWhen;

    public function __construct(?string $sendWhen)
    {
        $this->numRows = $sendWhen === null ? 0 : 1;
        $this->sendWhen = $sendWhen;
    }
}
