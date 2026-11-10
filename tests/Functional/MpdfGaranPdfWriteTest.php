<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MpdfGaranPdfWriteTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../../..';
    private const ORDER_ID_ENV = 'LEGAL_GUARANTEE_ORDER_ID';
    private const ORDER_ITEM_A_ENV = 'LEGAL_GUARANTEE_ORDER_ITEM_A_ID';
    private const ORDER_ITEM_B_ENV = 'LEGAL_GUARANTEE_ORDER_ITEM_B_ID';
    private const ORDER_ITEM_C_ENV = 'LEGAL_GUARANTEE_ORDER_ITEM_C_ID';

    /**
     * @var list<array{id: int, garanVersion: string|null, garanBrand: string|null, garanModelIdentifier: string|null, garanDurationMonths: string|int|float|null}>
     */
    private array $originalSnapshotState = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSnapshotState = $this->readSnapshotState();
    }

    protected function tearDown(): void
    {
        if ([] !== $this->originalSnapshotState) {
            $this->writeSnapshotState($this->originalSnapshotState);
        }

        parent::tearDown();
    }

    public function testOrderAttachmentCommandProducesInspectableGaranAndGllPdfsForFixtureF2(): void
    {
        $this->writeSnapshotState([
            $this->createSnapshotFixture($this->getOrderItemIds()[0], 'ACME', 'X100', '3'),
            $this->createSnapshotFixture($this->getOrderItemIds()[1], 'ACME', 'X100', '3'),
            $this->createSnapshotFixture($this->getOrderItemIds()[2], 'ACME', 'X100', '3'),
        ]);

        $relativePaths = $this->runOrderAttachmentCommand($this->getOrderId());
        $garanPaths = array_values(array_filter(
            $relativePaths,
            static fn (string $relativePath): bool => str_contains($relativePath, '/garantielabels/')
        ));
        $gllPaths = array_values(array_filter(
            $relativePaths,
            static fn (string $relativePath): bool => str_contains($relativePath, '/legal-guarantee/gll/')
        ));

        self::assertCount(1, $garanPaths);
        self::assertCount(1, $gllPaths);

        $garanInspector = new LegalGuaranteePdfDictionaryInspector($this->resolveProjectPath($garanPaths[0]));
        self::assertSame(2, $garanInspector->getPageCount());
        $garanMediaBoxes = $garanInspector->getPageMediaBoxesInMillimetres();
        self::assertCount(1, $garanMediaBoxes);
        self::assertLessThanOrEqual(0.5, abs($garanMediaBoxes[0]['widthMm'] - 148.0));
        self::assertLessThanOrEqual(0.5, abs($garanMediaBoxes[0]['heightMm'] - 210.0));

        $fontNames = $garanInspector->getFontNames();
        self::assertContains('Inter-Regular', $fontNames);
        self::assertContains('Inter-ExtraBold', $fontNames);
        self::assertNotContains('DejaVuSans', $fontNames);

        self::assertSame(
            ['https://europa.eu/youreurope/commercial-guarantee-durability/'],
            $garanInspector->getLinkUris()
        );
        self::assertLessThan(500000, $garanInspector->getFileSize());

        $gllInspector = new LegalGuaranteePdfDictionaryInspector($this->resolveProjectPath($gllPaths[0]));
        self::assertCount(1, $gllInspector->getPageMediaBoxesInMillimetres());

        if (!str_ends_with($gllPaths[0], '-en.pdf')) {
            self::assertNotEmpty($gllInspector->getLinkUris());
        }

        if (getenv('LEGAL_GUARANTEE_PHPUNIT_FORCE_FAILURE') === '1') {
            self::fail('Intentional failure to verify snapshot restoration.');
        }
    }

    /**
     * @return list<int>
     */
    private function getOrderItemIds(): array
    {
        return [
            $this->getIntegerEnv(self::ORDER_ITEM_A_ENV, 5),
            $this->getIntegerEnv(self::ORDER_ITEM_B_ENV, 6),
            $this->getIntegerEnv(self::ORDER_ITEM_C_ENV, 4),
        ];
    }

    private function getOrderId(): int
    {
        return $this->getIntegerEnv(self::ORDER_ID_ENV, 4);
    }

    private function getIntegerEnv(string $environmentVariable, int $defaultValue): int
    {
        $rawValue = getenv($environmentVariable);

        if (false === $rawValue || '' === $rawValue) {
            return $defaultValue;
        }

        $parsedValue = filter_var($rawValue, FILTER_VALIDATE_INT);

        if (false === $parsedValue) {
            throw new RuntimeException(sprintf('Environment variable %s must be an integer.', $environmentVariable));
        }

        return $parsedValue;
    }

    /**
     * @return array{id: int, garanVersion: string, garanBrand: string, garanModelIdentifier: string, garanDurationMonths: float}
     */
    private function createSnapshotFixture(
        int $itemId,
        string $brand,
        string $modelIdentifier,
        string $durationLabel
    ): array {
        return [
            'id' => $itemId,
            'garanVersion' => 'v1.0',
            'garanBrand' => $brand,
            'garanModelIdentifier' => $modelIdentifier,
            'garanDurationMonths' => $this->mapDurationLabelToSnapshotValue($durationLabel),
        ];
    }

    private function mapDurationLabelToSnapshotValue(string $durationLabel): int
    {
        return (int) round(((float) str_replace(',', '.', $durationLabel)) * 12);
    }

    /**
     * @return list<array{id: int, garanVersion: string|null, garanBrand: string|null, garanModelIdentifier: string|null, garanDurationMonths: string|int|float|null}>
     */
    private function readSnapshotState(): array
    {
        $itemIds = $this->getOrderItemIds();
        $query = sprintf(
            <<<'SQL'
SELECT JSON_OBJECT(
  'id', id,
  'garanVersion', garanVersion,
  'garanBrand', garanBrand,
  'garanModelIdentifier', garanModelIdentifier,
  'garanDurationMonths', garanDurationMonths
)
FROM tl_ls_shop_orders_items
WHERE id IN (%s)
ORDER BY FIELD(id, %s);
SQL,
            implode(', ', $itemIds),
            implode(', ', $itemIds),
        );

        $rows = array_filter(explode("\n", trim($this->runMysqlQuery($query))), static fn (string $row): bool => $row !== '');

        return array_map(
            static fn (string $row): array => json_decode($row, true, 512, JSON_THROW_ON_ERROR),
            $rows
        );
    }

    /**
     * @param list<array{id: int, garanVersion: string|null, garanBrand: string|null, garanModelIdentifier: string|null, garanDurationMonths: string|int|float|null}> $itemStates
     */
    private function writeSnapshotState(array $itemStates): void
    {
        foreach ($itemStates as $itemState) {
            $query = sprintf(
                <<<'SQL'
UPDATE tl_ls_shop_orders_items
SET
  garanVersion = %s,
  garanBrand = %s,
  garanModelIdentifier = %s,
  garanDurationMonths = %s
WHERE id = %d;
SQL,
                $this->toSqlValue($itemState['garanVersion']),
                $this->toSqlValue($itemState['garanBrand']),
                $this->toSqlValue($itemState['garanModelIdentifier']),
                $this->toSqlValue($itemState['garanDurationMonths']),
                $itemState['id'],
            );

            $this->runMysqlQuery($query);
        }
    }

    /**
     * @return list<string>
     */
    private function runOrderAttachmentCommand(int $orderId): array
    {
        $command = ['php', 'vendor/bin/contao-console', 'merconis:legal-guarantee:order-attachments', (string) $orderId];
        $output = $this->runProcess($command);

        return array_values(array_filter(
            array_map('trim', explode("\n", $output)),
            static fn (string $line): bool => $line !== ''
        ));
    }

    private function runMysqlQuery(string $query): string
    {
        return $this->runProcess(
            ['mysql', '--default-character-set=utf8mb4', '--batch', '--raw', '--skip-column-names', 'db'],
            $query . "\n"
        );
    }

    private function resolveProjectPath(string $relativePath): string
    {
        $directPath = self::PROJECT_ROOT . '/' . $relativePath;

        if (is_file($directPath)) {
            return $directPath;
        }

        if (str_starts_with($relativePath, 'bundles/')) {
            $publicPath = self::PROJECT_ROOT . '/public/' . $relativePath;

            if (is_file($publicPath)) {
                return $publicPath;
            }
        }

        return $directPath;
    }

    /**
     * @param list<string> $command
     */
    private function runProcess(array $command, string $stdin = ''): string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, self::PROJECT_ROOT);

        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('Could not start process: %s', implode(' ', $command)));
        }

        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf(
                "Process failed with exit code %d.\nCommand: %s\nSTDERR: %s",
                $exitCode,
                implode(' ', $command),
                trim((string) $stderr),
            ));
        }

        return (string) $stdout;
    }

    /**
     * @param string|int|float|null $value
     */
    private function toSqlValue(string|int|float|null $value): string
    {
        if (null === $value) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . str_replace(
            ["\\", "'"],
            ["\\\\", "\\'"],
            $value,
        ) . "'";
    }
}

final class LegalGuaranteePdfDictionaryInspector
{
    private string $pdfContents;
    private string $searchableContents;

    public function __construct(private readonly string $pdfPath)
    {
        $pdfContents = file_get_contents($this->pdfPath);

        if (false === $pdfContents) {
            throw new RuntimeException(sprintf('Could not read PDF file: %s', $this->pdfPath));
        }

        $this->pdfContents = $pdfContents;
        $this->searchableContents = $this->buildSearchableContents($pdfContents);
    }

    public function getFileSize(): int
    {
        return strlen($this->pdfContents);
    }

    public function getPageCount(): int
    {
        preg_match_all(
            '/<<(?:(?!>>).)*\/Type\s*\/Pages\b(?:(?!>>).)*\/Count\s+(\d+)(?:(?!>>).)*>>/s',
            $this->searchableContents,
            $pageTreeMatches
        );

        if (!empty($pageTreeMatches[1])) {
            return max(array_map('intval', $pageTreeMatches[1]));
        }

        preg_match_all(
            '/<<(?:(?!>>).)*\/Type\s*\/Page\b(?:(?!>>).)*>>/s',
            $this->searchableContents,
            $matches
        );

        return count(array_unique($matches[0]));
    }

    /**
     * @return list<array{widthMm: float, heightMm: float}>
     */
    public function getPageMediaBoxesInMillimetres(): array
    {
        preg_match_all(
            '/\/MediaBox\s*\[\s*([-0-9.]+)\s+([-0-9.]+)\s+([-0-9.]+)\s+([-0-9.]+)\s*\]/',
            $this->searchableContents,
            $matches,
            PREG_SET_ORDER
        );

        $mediaBoxes = [];
        $seenMediaBoxes = [];

        foreach ($matches as $match) {
            $mediaBoxKey = implode('|', [$match[1], $match[2], $match[3], $match[4]]);

            if (isset($seenMediaBoxes[$mediaBoxKey])) {
                continue;
            }

            $seenMediaBoxes[$mediaBoxKey] = true;
            $widthPoints = (float) $match[3] - (float) $match[1];
            $heightPoints = (float) $match[4] - (float) $match[2];

            $mediaBoxes[] = [
                'widthMm' => round($widthPoints * 25.4 / 72.0, 3),
                'heightMm' => round($heightPoints * 25.4 / 72.0, 3),
            ];
        }

        return $mediaBoxes;
    }

    /**
     * @return list<string>
     */
    public function getFontNames(): array
    {
        preg_match_all('/\/(?:BaseFont|FontName)\s*\/([A-Za-z0-9.+_-]+)/', $this->searchableContents, $matches);

        return array_values(array_unique(array_map(
            static function (string $fontName): string {
                return preg_replace('/^[A-Z]{6}\+/', '', $fontName) ?? $fontName;
            },
            $matches[1]
        )));
    }

    /**
     * @return list<string>
     */
    public function getLinkUris(): array
    {
        preg_match_all('/\/URI\s*\((.*?)\)/s', $this->searchableContents, $matches);

        return array_values(array_unique(array_map(
            static fn (string $uri): string => stripcslashes($uri),
            $matches[1]
        )));
    }

    private function buildSearchableContents(string $pdfContents): string
    {
        $segments = [$pdfContents];
        preg_match_all('/\d+\s+\d+\s+obj(.*?)endobj/s', $pdfContents, $matches);

        foreach ($matches[1] as $objectBody) {
            $streamContents = $this->extractStreamContents($objectBody);

            if (null === $streamContents) {
                continue;
            }

            if (!preg_match('/\/Filter\s*(?:\[[^\]]*\/FlateDecode[^\]]*\]|\/FlateDecode)/', $objectBody)) {
                continue;
            }

            $decodedStream = zlib_decode($streamContents);

            if (false === $decodedStream) {
                continue;
            }

            if (preg_match('/\/Type\s*\/ObjStm\b/', $objectBody) && preg_match('/\/First\s+(\d+)/', $objectBody, $firstMatch)) {
                $decodedStream = substr($decodedStream, (int) $firstMatch[1]);
            }

            $segments[] = $decodedStream;
        }

        return implode("\n", $segments);
    }

    private function extractStreamContents(string $objectBody): ?string
    {
        $streamPosition = strpos($objectBody, 'stream');
        $endStreamPosition = strpos($objectBody, 'endstream');

        if (false === $streamPosition || false === $endStreamPosition || $endStreamPosition <= $streamPosition) {
            return null;
        }

        $streamStart = $streamPosition + strlen('stream');

        if (substr($objectBody, $streamStart, 2) === "\r\n") {
            $streamStart += 2;
        } elseif (substr($objectBody, $streamStart, 1) === "\n" || substr($objectBody, $streamStart, 1) === "\r") {
            $streamStart += 1;
        }

        return substr($objectBody, $streamStart, $endStreamPosition - $streamStart);
    }
}
