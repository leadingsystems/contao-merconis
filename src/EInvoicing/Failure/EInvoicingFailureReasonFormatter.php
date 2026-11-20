<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Failure;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Throwable;

/**
 * Überführt Fehlergründe in `[<CODE>] <Beschreibung>` in einer
 * angegebenen Sprache. Die Beschreibungen stammen ausschließlich aus
 * der Sprachdatei `einvoicing`.
 */
final class EInvoicingFailureReasonFormatter
{
    private const LANGUAGE_FILE = 'einvoicing';

    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    /**
     * @return list<string>
     */
    public function formatThrowable(Throwable $throwable, string $language): array
    {
        return $this->formatAll(EInvoicingFailureReasonMapper::fromThrowable($throwable), $language);
    }

    /**
     * @param list<EInvoicingFailureReason> $failureReasons
     *
     * @return list<string>
     */
    public function formatAll(array $failureReasons, string $language): array
    {
        /** @var array<string, string|array{withoutParameters: string, withParameters: string}> $descriptionsByCode */
        $descriptionsByCode = $this->loadLanguageStrings($language)['einvoicingFailureReasons'];

        return array_map(
            fn (EInvoicingFailureReason $failureReason): string => $this->formatWithDescriptions($failureReason, $descriptionsByCode),
            $failureReasons
        );
    }

    public function format(EInvoicingFailureReason $failureReason, string $language): string
    {
        return $this->formatAll([$failureReason], $language)[0];
    }

    /**
     * @param array<string, string|array{withoutParameters: string, withParameters: string}> $descriptionsByCode
     */
    private function formatWithDescriptions(EInvoicingFailureReason $failureReason, array $descriptionsByCode): string
    {
        $descriptionEntry = $descriptionsByCode[$failureReason->code->value];

        if (is_array($descriptionEntry)) {
            $descriptionEntry = $failureReason->parameters === []
                ? $descriptionEntry['withoutParameters']
                : $descriptionEntry['withParameters'];
        }

        $placeholderReplacements = [];

        foreach ($failureReason->parameters as $parameterName => $parameterValue) {
            $placeholderReplacements['{' . $parameterName . '}'] = $parameterValue;
        }

        return sprintf('[%s] %s', $failureReason->code->value, strtr($descriptionEntry, $placeholderReplacements));
    }

    /**
     * Lädt die Sprachdatei für die angegebene Sprache und liefert die
     * E-Rechnungs-Texte unterhalb von
     * `$GLOBALS['TL_LANG']['lsShopEinvoicing']['MSC']['ls_contao-merconis']`.
     * Der vorherige Zustand von `$GLOBALS['TL_LANG']` wird danach vollständig
     * wiederhergestellt, damit die global geladene Sprache unverändert bleibt.
     *
     * @return array<string, mixed>
     */
    public function loadLanguageStrings(string $language): array
    {
        $this->framework->initialize();

        $languageStringsExisted = array_key_exists('TL_LANG', $GLOBALS);
        $previousLanguageStrings = $GLOBALS['TL_LANG'] ?? null;

        try {
            $this->framework->getAdapter(System::class)->loadLanguageFile(self::LANGUAGE_FILE, $language, true);

            return $GLOBALS['TL_LANG']['lsShopEinvoicing']['MSC']['ls_contao-merconis'];
        } finally {
            if ($languageStringsExisted) {
                $GLOBALS['TL_LANG'] = $previousLanguageStrings;
            } else {
                unset($GLOBALS['TL_LANG']);
            }
        }
    }
}
