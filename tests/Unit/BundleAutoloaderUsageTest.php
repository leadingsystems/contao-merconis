<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Produktiver Code darf das bundle-eigene `vendor/autoload.php` nicht laden.
 * Das bundle-eigene `vendor/` existiert nur für PHPUnit; zur Laufzeit stellt
 * der Composer-Autoloader der Host-Anwendung alle Abhängigkeiten bereit.
 */
final class BundleAutoloaderUsageTest extends TestCase
{
    private const AUTOLOADER_REFERENCE_PATTERN = '/autoload\.php/i';

    public function testSourceCodeDoesNotReferenceAutoloader(): void
    {
        $sourceFilePaths = $this->collectPhpFilePathsRecursively($this->getBundleRootPath() . '/src');

        self::assertNotSame([], $sourceFilePaths);
        self::assertSame([], $this->findFilePathsReferencingAutoloader($sourceFilePaths));
    }

    public function testManualMigrationScriptsDoNotReferenceAutoloader(): void
    {
        $migrationScriptPaths = glob($this->getBundleRootPath() . '/pcf-migrations/*.php');

        self::assertIsArray($migrationScriptPaths);
        self::assertNotSame([], $migrationScriptPaths);
        self::assertSame([], $this->findFilePathsReferencingAutoloader($migrationScriptPaths));
    }

    public function testReferencePatternDetectsBundleAutoloaderInclusion(): void
    {
        $bundleAutoloaderInclusion = "require_once dirname(__DIR__, 3) . '/vendor/autoload.php';";

        self::assertMatchesRegularExpression(self::AUTOLOADER_REFERENCE_PATTERN, $bundleAutoloaderInclusion);
    }

    private function getBundleRootPath(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return list<string>
     */
    private function collectPhpFilePathsRecursively(string $directoryPath): array
    {
        $phpFilePaths = [];
        $fileIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directoryPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($fileIterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $phpFilePaths[] = $fileInfo->getPathname();
            }
        }

        sort($phpFilePaths);

        return $phpFilePaths;
    }

    /**
     * @param list<string> $phpFilePaths
     *
     * @return list<string>
     */
    private function findFilePathsReferencingAutoloader(array $phpFilePaths): array
    {
        $bundleRootPath = $this->getBundleRootPath();
        $referencingFilePaths = [];

        foreach ($phpFilePaths as $phpFilePath) {
            $fileContent = file_get_contents($phpFilePath);

            self::assertIsString($fileContent);

            if (preg_match(self::AUTOLOADER_REFERENCE_PATTERN, $fileContent) === 1) {
                $referencingFilePaths[] = substr($phpFilePath, strlen($bundleRootPath) + 1);
            }
        }

        return $referencingFilePaths;
    }
}
