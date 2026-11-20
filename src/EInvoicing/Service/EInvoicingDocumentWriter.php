<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Service;

use LeadingSystems\MerconisBundle\EInvoicing\Exception\EInvoicingGenerationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Model\GeneratedZugferdDocument;

final class EInvoicingDocumentWriter
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function writePdf(
        GeneratedZugferdDocument $document,
        string $targetDirectory,
        bool $uniqueFileName = true,
    ): string {
        return $this->writeContent(
            $targetDirectory,
            $document->fileName,
            $document->pdfContent,
            $uniqueFileName
        );
    }

    public function writeXml(
        GeneratedZugferdDocument $document,
        string $targetDirectory,
        bool $uniqueFileName = true,
    ): string {
        $xmlFileName = preg_replace('/\.pdf$/', '.xml', $document->fileName) ?? ($document->fileName . '.xml');

        return $this->writeContent(
            $targetDirectory,
            $xmlFileName,
            $document->xmlContent,
            $uniqueFileName
        );
    }

    private function writeContent(
        string $targetDirectory,
        string $fileName,
        string $content,
        bool $uniqueFileName,
    ): string {
        $absoluteDirectory = $this->resolveAbsolutePath($targetDirectory);

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw EInvoicingGenerationException::forReason(
                EInvoicingErrorCode::OUTPUT_DIRECTORY_NOT_CREATABLE,
                ['directory' => $absoluteDirectory]
            );
        }

        $absolutePath = $absoluteDirectory . '/' . ltrim($fileName, '/');

        if ($uniqueFileName) {
            $absolutePath = $this->resolveUniquePath($absolutePath);
        }

        if (file_put_contents($absolutePath, $content) === false) {
            throw EInvoicingGenerationException::forReason(
                EInvoicingErrorCode::OUTPUT_FILE_NOT_WRITABLE,
                ['file' => $absolutePath]
            );
        }

        return $this->toRelativePath($absolutePath);
    }

    private function resolveAbsolutePath(string $path): string
    {
        $normalizedPath = trim($path);

        if ($normalizedPath === '') {
            throw EInvoicingGenerationException::forReason(EInvoicingErrorCode::OUTPUT_DIRECTORY_MISSING);
        }

        if ($normalizedPath[0] === '/') {
            return rtrim($normalizedPath, '/');
        }

        return rtrim($this->projectDir, '/') . '/' . trim($normalizedPath, '/');
    }

    private function resolveUniquePath(string $absolutePath): string
    {
        if (!file_exists($absolutePath)) {
            return $absolutePath;
        }

        $extension = pathinfo($absolutePath, PATHINFO_EXTENSION);
        $basePath = $extension === ''
            ? $absolutePath
            : substr($absolutePath, 0, -strlen($extension) - 1);

        do {
            $candidate = $basePath . '-' . bin2hex(random_bytes(4));

            if ($extension !== '') {
                $candidate .= '.' . $extension;
            }
        } while (file_exists($candidate));

        return $candidate;
    }

    private function toRelativePath(string $absolutePath): string
    {
        $projectRoot = rtrim($this->projectDir, '/') . '/';

        if (str_starts_with($absolutePath, $projectRoot)) {
            return substr($absolutePath, strlen($projectRoot));
        }

        return $absolutePath;
    }
}
