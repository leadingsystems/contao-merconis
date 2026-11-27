<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Support;

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\System;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Bootet den Contao-Anwendungskontext des Host-Projekts für Functional-Tests
 * (Implementierungsentscheidung 0010) und stellt Hilfen zum Lesen gerenderter Bausteine
 * bereit. Tests, die ihn verwenden, laufen in eigenen Prozessen, weil das Booten globalen
 * Zustand (Container, `$GLOBALS`) hinterlässt.
 */
final class ContaoTestApplication
{
    public static function getProjectDir(): string
    {
        return dirname(__DIR__, 5);
    }

    public static function boot(string $language): void
    {
        $kernel = ContaoKernel::fromInput(self::getProjectDir(), new ArrayInput(['--env' => 'prod']));
        $kernel->boot();

        $container = $kernel->getContainer();
        System::setContainer($container);
        $container->get('contao.framework')->initialize();

        System::loadLanguageFile('default', $language);
        $GLOBALS['objPage'] = (object) ['language' => $language, 'templateGroup' => ''];
        $GLOBALS['TL_CSS'] = [];
    }

    public static function createXPath(string $html): DOMXPath
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>'
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (!$loaded) {
            throw new RuntimeException('Rendered markup could not be parsed.');
        }

        return new DOMXPath($document);
    }

    /**
     * @return list<DOMElement>
     */
    public static function findByClass(DOMXPath $xpath, string $className, ?DOMElement $context = null): array
    {
        $nodes = $xpath->query(
            sprintf('.//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $className),
            $context ?? $xpath->document->documentElement
        );
        $elements = [];

        foreach ($nodes ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    /**
     * @return list<string>
     */
    public static function collectIds(DOMXPath $xpath): array
    {
        $ids = [];

        foreach ($xpath->query('//*[@id]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $ids[] = $node->getAttribute('id');
            }
        }

        return $ids;
    }
}
