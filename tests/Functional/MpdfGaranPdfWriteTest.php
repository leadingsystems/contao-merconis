<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

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
    /**
     * @var list<string>
     */
    private array $generatedRelativePaths = [];
    /**
     * @var array{id: int, orderNr: string|null, orderDateUnixTimestamp: int|null}|null
     */
    private ?array $originalOrderState = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSnapshotState = $this->readSnapshotState();
        $this->originalOrderState = $this->readOrderState();
    }

    protected function tearDown(): void
    {
        $this->deleteGeneratedAttachmentFiles();

        if (null !== $this->originalOrderState) {
            $this->writeOrderState($this->originalOrderState);
        }

        if ([] !== $this->originalSnapshotState) {
            $this->writeSnapshotState($this->originalSnapshotState);
        }

        parent::tearDown();
    }

    public function testOrderAttachmentCommandProducesExpectedStructuralPdfOutputForOneTwoAndThreePages(): void
    {
        $itemIds = $this->getOrderItemIds();
        $scenarios = [
            'one-page fixture F4' => [
                'expectedFormXObjectCount' => 2,
                'expectedPageCount' => 1,
                'fixtures' => [
                    $this->createEmptySnapshotFixture($itemIds[0]),
                    $this->createEmptySnapshotFixture($itemIds[1]),
                    $this->createSnapshotFixture($itemIds[2], 'ACME', 'X100', '20,5'),
                ],
            ],
            'two-page fixture F2' => [
                'expectedFormXObjectCount' => 2,
                'expectedPageCount' => 2,
                'fixtures' => [
                    $this->createSnapshotFixture($itemIds[0], 'ACME', 'X100', '3'),
                    $this->createSnapshotFixture($itemIds[1], 'ACME', 'X100', '3'),
                    $this->createSnapshotFixture($itemIds[2], 'ACME', 'X100', '3'),
                ],
            ],
            'three-page mixed fixtures' => [
                'expectedFormXObjectCount' => 4,
                'expectedPageCount' => 3,
                'fixtures' => [
                    $this->createSnapshotFixture($itemIds[0], 'Mustermann Qualitätshersteller GmbH & Co. KG', 'FooBar', '2,5'),
                    $this->createSnapshotFixture($itemIds[1], 'ACME', 'X100', '20,5'),
                    $this->createSnapshotFixture($itemIds[2], 'ACME', 'X100', '3'),
                ],
            ],
        ];

        foreach ($scenarios as $label => $scenario) {
            $this->writeSnapshotState($scenario['fixtures']);
            $inspectionResult = $this->inspectGeneratedAttachments($this->getOrderId());
            $garanInspector = $inspectionResult['garanInspector'];

            self::assertSame($scenario['expectedPageCount'], $garanInspector->getPageCount(), $label);
            self::assertSame($scenario['expectedFormXObjectCount'], $garanInspector->getFormXObjectCount(), $label);
            self::assertSame(1, $garanInspector->countLargeFormXObjects(10000), $label);
            self::assertGreaterThanOrEqual(1.0, $garanInspector->getMinimumTextFontSize(), $label);
            self::assertCommonPdfProperties($inspectionResult['garanPath'], $inspectionResult['gllPath']);
        }
    }

    public function testOrderAttachmentCommandReplacesBrokenLabelWithNoticePageAndKeepsSharedStaticPart(): void
    {
        $itemIds = $this->getOrderItemIds();
        $this->writeSnapshotState([
            $this->createSnapshotFixture($itemIds[0], 'Mustermann Qualitätshersteller GmbH & Co. KG', 'FooBar', '2,5'),
            $this->createSnapshotFixture($itemIds[1], 'ACME', 'X100', '20,5', 'v9.9'),
            $this->createSnapshotFixture($itemIds[2], 'ACME', 'X100', '3'),
        ]);

        $inspectionResult = $this->inspectGeneratedAttachments($this->getOrderId());
        $garanInspector = $inspectionResult['garanInspector'];

        self::assertSame(3, $garanInspector->getPageCount());
        self::assertSame(3, $garanInspector->getFormXObjectCount());
        self::assertSame(1, $garanInspector->countLargeFormXObjects(10000));
        self::assertGreaterThanOrEqual(1.0, $garanInspector->getMinimumTextFontSize());
        self::assertCommonPdfProperties($inspectionResult['garanPath'], $inspectionResult['gllPath']);

        if (getenv('LEGAL_GUARANTEE_PHPUNIT_FORCE_FAILURE') === '1') {
            self::fail('Intentional failure to verify snapshot restoration.');
        }
    }

    public function testOrderAttachmentCommandCreatesDatedFilesWithoutOverwritingExistingPdf(): void
    {
        $timestamp = 1_794_441_600;
        $orderNumber = 'stage13-functional-order';
        $this->writeOrderState([
            'id' => $this->getOrderId(),
            'orderNr' => $orderNumber,
            'orderDateUnixTimestamp' => $timestamp,
        ]);

        $firstRunPaths = $this->runOrderAttachmentCommand($this->getOrderId());
        $secondRunPaths = $this->runOrderAttachmentCommand($this->getOrderId());
        $firstGaranPath = $this->extractSingleGaranPath($firstRunPaths);
        $secondGaranPath = $this->extractSingleGaranPath($secondRunPaths);

        self::assertSame(
            'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/2026/11/12/garan-labels-stage13-functional-order.pdf',
            $firstGaranPath
        );
        self::assertSame(
            'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/2026/11/12/garan-labels-stage13-functional-order_2.pdf',
            $secondGaranPath
        );
        self::assertNotSame($firstGaranPath, $secondGaranPath);
        self::assertFileExists($this->resolveProjectPath($firstGaranPath));
        self::assertFileExists($this->resolveProjectPath($secondGaranPath));
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
        string $durationLabel,
        string $garanVersion = 'v1.0'
    ): array {
        return [
            'id' => $itemId,
            'garanVersion' => $garanVersion,
            'garanBrand' => $brand,
            'garanModelIdentifier' => $modelIdentifier,
            'garanDurationMonths' => $this->mapDurationLabelToSnapshotValue($durationLabel),
        ];
    }

    /**
     * @return array{id: int, garanVersion: string|null, garanBrand: string|null, garanModelIdentifier: string|null, garanDurationMonths: string|int|float|null}
     */
    private function createEmptySnapshotFixture(int $itemId): array
    {
        return [
            'id' => $itemId,
            'garanVersion' => '',
            'garanBrand' => '',
            'garanModelIdentifier' => '',
            'garanDurationMonths' => null,
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
     * @return array{id: int, orderNr: string|null, orderDateUnixTimestamp: int|null}
     */
    private function readOrderState(): array
    {
        $query = sprintf(
            <<<'SQL'
SELECT JSON_OBJECT(
  'id', id,
  'orderNr', orderNr,
  'orderDateUnixTimestamp', orderDateUnixTimestamp
)
FROM tl_ls_shop_orders
WHERE id = %d
LIMIT 1;
SQL,
            $this->getOrderId()
        );
        $output = trim($this->runMysqlQuery($query));

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
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
     * @param array{id: int, orderNr: string|null, orderDateUnixTimestamp: int|null} $orderState
     */
    private function writeOrderState(array $orderState): void
    {
        $query = sprintf(
            <<<'SQL'
UPDATE tl_ls_shop_orders
SET
  orderNr = %s,
  orderDateUnixTimestamp = %s
WHERE id = %d;
SQL,
            $this->toSqlValue($orderState['orderNr']),
            $this->toSqlValue($orderState['orderDateUnixTimestamp']),
            $orderState['id']
        );

        $this->runMysqlQuery($query);
    }

    /**
     * @return list<string>
     */
    private function runOrderAttachmentCommand(int $orderId): array
    {
        $this->clearProdCache();
        $command = ['php', 'vendor/bin/contao-console', 'merconis:legal-guarantee:order-attachments', (string) $orderId];
        $output = $this->runProcess($command);
        $relativePaths = array_values(array_filter(
            array_map('trim', explode("\n", $output)),
            static fn (string $line): bool => $line !== ''
        ));

        foreach ($relativePaths as $relativePath) {
            if (str_contains($relativePath, '/garantielabels/')) {
                $this->generatedRelativePaths[] = $relativePath;
            }
        }

        return $relativePaths;
    }

    private function clearProdCache(): void
    {
        $prodCacheDirectory = self::PROJECT_ROOT . '/var/cache/prod';

        if (is_dir($prodCacheDirectory)) {
            (new Filesystem())->remove($prodCacheDirectory);
        }
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

    private function extractSingleGaranPath(array $relativePaths): string
    {
        $garanPaths = array_values(array_filter(
            $relativePaths,
            static fn (string $relativePath): bool => str_contains($relativePath, '/garantielabels/')
        ));

        self::assertCount(1, $garanPaths);

        return $garanPaths[0];
    }

    private function deleteGeneratedAttachmentFiles(): void
    {
        foreach (array_unique($this->generatedRelativePaths) as $generatedRelativePath) {
            $absolutePath = $this->resolveProjectPath($generatedRelativePath);

            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }

    /**
     * @return array{
     *   garanInspector: LegalGuaranteePdfDictionaryInspector,
     *   garanPath: string,
     *   gllPath: string
     * }
     */
    private function inspectGeneratedAttachments(int $orderId): array
    {
        $relativePaths = $this->runOrderAttachmentCommand($orderId);
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

        return [
            'garanInspector' => new LegalGuaranteePdfDictionaryInspector($this->resolveProjectPath($garanPaths[0])),
            'garanPath' => $garanPaths[0],
            'gllPath' => $gllPaths[0],
        ];
    }

    private function assertCommonPdfProperties(string $garanPath, string $gllPath): void
    {
        $garanInspector = new LegalGuaranteePdfDictionaryInspector($this->resolveProjectPath($garanPath));
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

        $gllInspector = new LegalGuaranteePdfDictionaryInspector($this->resolveProjectPath($gllPath));
        self::assertCount(1, $gllInspector->getPageMediaBoxesInMillimetres());

        if (!str_ends_with($gllPath, '-en.pdf')) {
            self::assertNotEmpty($gllInspector->getLinkUris());
        }
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

    public function getFormXObjectCount(): int
    {
        return count($this->getFormXObjectDecodedStreamLengths());
    }

    public function countLargeFormXObjects(int $minimumDecodedLength): int
    {
        return count(array_filter(
            $this->getFormXObjectDecodedStreamLengths(),
            static fn (int $decodedLength): bool => $decodedLength > $minimumDecodedLength
        ));
    }

    public function getMinimumTextFontSize(): float
    {
        preg_match_all('/(?:^|[^0-9.])([0-9]+(?:\.[0-9]+)?)\s+Tf\b/', $this->searchableContents, $matches);

        if (empty($matches[1])) {
            throw new RuntimeException('No PDF text objects were found.');
        }

        return min(array_map('floatval', $matches[1]));
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

    /**
     * @return list<int>
     */
    private function getFormXObjectDecodedStreamLengths(): array
    {
        $decodedLengths = [];
        preg_match_all('/\d+\s+\d+\s+obj(.*?)endobj/s', $this->pdfContents, $matches);

        foreach ($matches[1] as $objectBody) {
            if (!preg_match('/\/Subtype\s*\/Form\b/', $objectBody)) {
                continue;
            }

            $streamContents = $this->extractStreamContents($objectBody);

            if (null === $streamContents) {
                continue;
            }

            $decodedLengths[] = $this->decodePdfStream($objectBody, $streamContents);
        }

        return $decodedLengths;
    }

    private function decodePdfStream(string $objectBody, string $streamContents): int
    {
        if (!preg_match('/\/Filter\s*(?:\[[^\]]*\/FlateDecode[^\]]*\]|\/FlateDecode)/', $objectBody)) {
            return strlen($streamContents);
        }

        $decodedStream = zlib_decode($streamContents);

        if (false === $decodedStream) {
            return strlen($streamContents);
        }

        if (preg_match('/\/Type\s*\/ObjStm\b/', $objectBody) && preg_match('/\/First\s+(\d+)/', $objectBody, $firstMatch)) {
            $decodedStream = substr($decodedStream, (int) $firstMatch[1]);
        }

        return strlen($decodedStream);
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
