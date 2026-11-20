<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Failure;

/**
 * Zentraler Katalog aller Fehlercodes der E-Rechnungs-Erzeugung.
 *
 * Fehlercodes werden ausschließlich über diese Enum referenziert. Die
 * lesbaren Beschreibungen stehen in den Sprachdateien
 * `languages/<sprache>/einvoicing.php` unter dem Code als Schlüssel.
 */
enum EInvoicingErrorCode: string
{
    // Eigene Erzeugungsfehler der Schicht
    case ORDER_NUMBER_MISSING = 'EINVOICING_ORDER_NUMBER_MISSING';
    case ORDER_NOT_FOUND = 'EINVOICING_ORDER_NOT_FOUND';
    case ORDER_ID_MISSING = 'EINVOICING_ORDER_ID_MISSING';
    case ORDER_ID_NOT_FOUND = 'EINVOICING_ORDER_ID_NOT_FOUND';
    case INVOICE_NUMBER_MISSING = 'EINVOICING_INVOICE_NUMBER_MISSING';
    case OUTPUT_DIRECTORY_MISSING = 'EINVOICING_OUTPUT_DIRECTORY_MISSING';
    case OUTPUT_DIRECTORY_NOT_CREATABLE = 'EINVOICING_OUTPUT_DIRECTORY_NOT_CREATABLE';
    case OUTPUT_FILE_NOT_WRITABLE = 'EINVOICING_OUTPUT_FILE_NOT_WRITABLE';

    // Fehlende oder ungültige EN-16931-Werte
    case MISSING_BT_1 = 'EINVOICING_MISSING_BT_1';
    case MISSING_BT_5 = 'EINVOICING_MISSING_BT_5';
    case MISSING_BT_27 = 'EINVOICING_MISSING_BT_27';
    case MISSING_BT_31_OR_BT_32 = 'EINVOICING_MISSING_BT_31_OR_BT_32';
    case MISSING_BT_34 = 'EINVOICING_MISSING_BT_34';
    case MISSING_BT_34_1 = 'EINVOICING_MISSING_BT_34_1';
    case INVALID_BT_34_1 = 'EINVOICING_INVALID_BT_34_1';
    case MISSING_BT_35 = 'EINVOICING_MISSING_BT_35';
    case MISSING_BT_37 = 'EINVOICING_MISSING_BT_37';
    case MISSING_BT_38 = 'EINVOICING_MISSING_BT_38';
    case MISSING_BT_40 = 'EINVOICING_MISSING_BT_40';
    case MISSING_BT_44 = 'EINVOICING_MISSING_BT_44';
    case MISSING_BT_50 = 'EINVOICING_MISSING_BT_50';
    case MISSING_BT_52 = 'EINVOICING_MISSING_BT_52';
    case MISSING_BT_53 = 'EINVOICING_MISSING_BT_53';
    case MISSING_BT_55 = 'EINVOICING_MISSING_BT_55';
    case MISSING_BT_80 = 'EINVOICING_MISSING_BT_80';
    case MISSING_BT_81 = 'EINVOICING_MISSING_BT_81';
    case INVALID_BT_81 = 'EINVOICING_INVALID_BT_81';
    case MISSING_BT_84 = 'EINVOICING_MISSING_BT_84';
    case MISSING_BT_87 = 'EINVOICING_MISSING_BT_87';
    case MISSING_BT_89 = 'EINVOICING_MISSING_BT_89';
    case MISSING_BT_90 = 'EINVOICING_MISSING_BT_90';
    case MISSING_BT_91 = 'EINVOICING_MISSING_BT_91';
    case MISSING_BT_103 = 'EINVOICING_MISSING_BT_103';
    case MISSING_BT_129 = 'EINVOICING_MISSING_BT_129';
    case INVALID_BT_129 = 'EINVOICING_INVALID_BT_129';
    case MISSING_BT_146 = 'EINVOICING_MISSING_BT_146';
    case MISSING_BT_152 = 'EINVOICING_MISSING_BT_152';
    case MISSING_BT_153 = 'EINVOICING_MISSING_BT_153';
    case MISSING_BG_23 = 'EINVOICING_MISSING_BG_23';
    case MISSING_BG_25 = 'EINVOICING_MISSING_BG_25';

    // Checkout-Snapshot fehlt oder ist ungültig
    case SNAPSHOT_MISSING_TAX_COUNTRY = 'EINVOICING_SNAPSHOT_MISSING_TAX_COUNTRY';
    case SNAPSHOT_MISSING_VAT_EXEMPTION_STATUS = 'EINVOICING_SNAPSHOT_MISSING_VAT_EXEMPTION_STATUS';
    case SNAPSHOT_INVALID_VAT_EXEMPTION_STATUS = 'EINVOICING_SNAPSHOT_INVALID_VAT_EXEMPTION_STATUS';
    case SNAPSHOT_MISSING_BT_81 = 'EINVOICING_SNAPSHOT_MISSING_BT_81';
    case SNAPSHOT_MISSING_BT_102 = 'EINVOICING_SNAPSHOT_MISSING_BT_102';
    case SNAPSHOT_MISSING_BT_151 = 'EINVOICING_SNAPSHOT_MISSING_BT_151';

    // Fehler, die nicht aus der E-Rechnungs-Schicht stammen
    case RUNTIME_ERROR = 'EINVOICING_RUNTIME_ERROR';

    /**
     * Namen der Parameter, die in die Beschreibung eingesetzt werden
     * (Platzhalter `{<name>}` in der Sprachvariable).
     *
     * @return list<string>
     */
    public function parameterNames(): array
    {
        return match ($this) {
            self::ORDER_NOT_FOUND => ['orderNumber'],
            self::ORDER_ID_NOT_FOUND => ['orderId'],
            self::OUTPUT_DIRECTORY_NOT_CREATABLE => ['directory'],
            self::OUTPUT_FILE_NOT_WRITABLE => ['file'],
            self::INVALID_BT_34_1 => ['scheme'],
            self::INVALID_BT_81,
            self::MISSING_BT_84,
            self::MISSING_BT_87,
            self::MISSING_BT_89,
            self::MISSING_BT_90,
            self::MISSING_BT_91 => ['paymentMeansCode'],
            self::MISSING_BT_103,
            self::SNAPSHOT_MISSING_BT_102 => ['chargeReason'],
            self::MISSING_BT_129,
            self::INVALID_BT_129,
            self::MISSING_BT_146,
            self::MISSING_BT_152,
            self::MISSING_BT_153,
            self::SNAPSHOT_MISSING_BT_151 => ['lineId'],
            self::RUNTIME_ERROR => ['message'],
            default => [],
        };
    }

    /**
     * Bei optionalen Parametern hält die Sprachdatei je eine
     * Beschreibung mit und ohne Parameter bereit
     * (`withParameters`, `withoutParameters`).
     */
    public function hasOptionalParameters(): bool
    {
        return $this === self::RUNTIME_ERROR;
    }
}
