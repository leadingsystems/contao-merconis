<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Support;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use LeadingSystems\MerconisBundle\EInvoicing\Service\EInvoicingFailureReporter;
use Merconis\Core\ls_shop_languageHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Ersetzt `System::loadLanguageFile()` in Unit-Tests. Lädt die
 * E-Rechnungs-Sprachdateien des Bundles wie Contao (zuerst `en`, dann die
 * angeforderte Sprache) und verändert zusätzlich einen weiteren Schlüssel
 * in `$GLOBALS['TL_LANG']`, wie es Contao mit `MSC.textDirection` tut.
 */
final class EInvoicingLanguageFileLoaderStub
{
    /**
     * @var list<array{0: string, 1: string, 2: bool}>
     */
    public static array $calls = [];

    public static bool $throwOnLoad = false;

    public static function reset(): void
    {
        self::$calls = [];
        self::$throwOnLoad = false;
    }

    public static function loadLanguageFile(string $name, string $language, bool $noCache = false): void
    {
        self::$calls[] = [$name, $language, $noCache];

        $languagesToLoad = $language === 'en' ? ['en'] : ['en', $language];

        foreach ($languagesToLoad as $languageToLoad) {
            require __DIR__ . '/../../src/Resources/contao/languages/' . $languageToLoad . '/' . $name . '.php';
        }

        $GLOBALS['TL_LANG']['MSC']['textDirection'] = 'loaded-by-stub-' . $language;

        if (self::$throwOnLoad) {
            throw new \RuntimeException('Language file loading failed.');
        }
    }
}

/**
 * Liefert konfigurierbare Werte für `Config::get()` in Unit-Tests.
 */
final class EInvoicingConfigStub
{
    /**
     * @var array<string, mixed>
     */
    public static array $values = [];

    public static function get(string $key): mixed
    {
        return self::$values[$key] ?? null;
    }
}

/**
 * Liefert eine konfigurierbare Fallback-Sprache für
 * `ls_shop_languageHelper::getFallbackLanguage()` in Unit-Tests.
 */
final class EInvoicingFallbackLanguageStub
{
    public static string $fallbackLanguage = 'de';

    public static function reset(): void
    {
        self::$fallbackLanguage = 'de';
    }

    public static function getFallbackLanguage(): string
    {
        return self::$fallbackLanguage;
    }
}

/**
 * Reporter, der den E-Mail-Versand protokolliert statt den
 * Contao-Mailer zu verwenden.
 */
final class EInvoicingRecordingFailureReporter extends EInvoicingFailureReporter
{
    /**
     * @var list<array{recipient: string, subject: string, text: string}>
     */
    public array $sentEmails = [];

    public ?\Throwable $emailSendingFailure = null;

    protected function sendEmail(string $recipient, string $subject, string $text): void
    {
        if ($this->emailSendingFailure !== null) {
            throw $this->emailSendingFailure;
        }

        $this->sentEmails[] = [
            'recipient' => $recipient,
            'subject' => $subject,
            'text' => $text,
        ];
    }
}

final class EInvoicingFailureTestSupport
{
    /**
     * Verbindet einen Framework-Mock mit den Stubs für Sprachdateien,
     * Konfiguration und Fallback-Sprache.
     */
    public static function configureFrameworkAdapters(MockObject&ContaoFramework $framework): ContaoFramework
    {
        $framework
            ->method('getAdapter')
            ->willReturnCallback(static fn (string $class): Adapter => match ($class) {
                System::class => new Adapter(EInvoicingLanguageFileLoaderStub::class),
                Config::class => new Adapter(EInvoicingConfigStub::class),
                ls_shop_languageHelper::class => new Adapter(EInvoicingFallbackLanguageStub::class),
            });

        return $framework;
    }
}
