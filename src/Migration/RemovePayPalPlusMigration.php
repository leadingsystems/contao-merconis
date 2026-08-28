<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Sichert vorhandene PayPal-Plus-Datensätze als CSV und entfernt sie anschließend
 * aus der Tabelle `tl_ls_shop_payment_methods`.
 *
 * Die CSV wird unter `files/merconis_backup/` abgelegt, damit sie über den
 * Contao-Dateimanager heruntergeladen werden kann.
 */
final class RemovePayPalPlusMigration extends AbstractMigration
{
    private const TABLE = 'tl_ls_shop_payment_methods';

    private const PAYPAL_PLUS_COLUMNS = [
        'id',
        'title',
        'alias',
        'published',
        'payPalPlus_clientID',
        'payPalPlus_clientSecret',
        'payPalPlus_liveMode',
        'payPalPlus_logMode',
        'payPalPlus_shipToFieldNameFirstname',
        'payPalPlus_shipToFieldNameLastname',
        'payPalPlus_shipToFieldNameStreet',
        'payPalPlus_shipToFieldNameCity',
        'payPalPlus_shipToFieldNamePostal',
        'payPalPlus_shipToFieldNameState',
        'payPalPlus_shipToFieldNameCountryCode',
        'payPalPlus_shipToFieldNamePhone',
    ];

    private Connection $connection;
    private string $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TABLE])) {
            return false;
        }

        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE `type` = ?',
            ['payPalPlus']
        );

        return $count > 0;
    }

    public function run(): MigrationResult
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM `' . self::TABLE . '` WHERE `type` = ?',
            ['payPalPlus']
        );

        if (\count($rows) === 0) {
            return $this->createResult(true, 'Keine PayPal-Plus-Datensätze gefunden.');
        }

        $availableColumns = $this->getAvailableBackupColumns($rows);
        $csvPath = $this->writeCsvBackup($rows, $availableColumns);

        $this->connection->executeStatement(
            'DELETE FROM `' . self::TABLE . '` WHERE `type` = ?',
            ['payPalPlus']
        );

        return $this->createResult(
            true,
            sprintf(
                '%d PayPal-Plus-Datensatz/-sätze gesichert nach "%s" und aus der Datenbank entfernt.',
                \count($rows),
                $csvPath
            )
        );
    }

    /**
     * Ermittelt, welche der gewünschten Backup-Spalten tatsächlich noch in der
     * DB vorhanden sind (Contao-Schema-Migration kann sie bereits entfernt haben).
     */
    private function getAvailableBackupColumns(array $rows): array
    {
        if (\count($rows) === 0) {
            return [];
        }

        $existingKeys = array_keys($rows[0]);

        return array_values(array_filter(
            self::PAYPAL_PLUS_COLUMNS,
            static fn (string $col): bool => \in_array($col, $existingKeys, true)
        ));
    }

    private function writeCsvBackup(array $rows, array $columns): string
    {
        $backupDir = $this->projectDir . '/files/merconis_backup';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $filename = 'paypal_plus_backup_' . date('Y-m-d_His') . '.csv';
        $filePath = $backupDir . '/' . $filename;

        $handle = fopen($filePath, 'w');

        if ($handle === false) {
            throw new \RuntimeException('CSV-Datei konnte nicht erstellt werden: ' . $filePath);
        }

        fputcsv($handle, $columns, ';');

        foreach ($rows as $row) {
            $csvRow = [];

            foreach ($columns as $column) {
                $csvRow[] = $row[$column] ?? '';
            }

            fputcsv($handle, $csvRow, ';');
        }

        fclose($handle);

        return 'files/merconis_backup/' . $filename;
    }
}
