<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\ProductData;

use Closure;
use Contao\Config;
use Doctrine\DBAL\Connection;

final class EnableGllSettingsMigrationManager
{
    public const CONFIG_FLAG = 'ls_shop_enableGllMigrationApplied';
    public const SUBMIT_NAME = 'ls_shop_enableGllMigrationRequested';

    /** @var Closure(string): mixed */
    private readonly Closure $configReader;

    /** @var Closure(string, string): void */
    private readonly Closure $configWriter;

    /**
     * @param ?Closure(string): mixed $configReader
     * @param ?Closure(string, string): void $configWriter
     */
    public function __construct(
        private readonly Connection $connection,
        ?Closure $configReader = null,
        ?Closure $configWriter = null,
    ) {
        $this->configReader = $configReader ?? static fn (string $configKey): mixed => Config::get($configKey);
        $this->configWriter = $configWriter ?? static function (string $configKey, string $configValue): void {
            Config::persist($configKey, $configValue);
            Config::set($configKey, $configValue);
        };
    }

    public function isApplied(): bool
    {
        return (string) ($this->configReader)(self::CONFIG_FLAG) !== '';
    }

    public function shouldApplyForSubmission(?string $submittedValue): bool
    {
        return !$this->isApplied() && $submittedValue === '1';
    }

    public function apply(): int
    {
        $affectedRows = $this->connection->executeStatement("
            UPDATE `tl_ls_shop_product`
            SET `enableGll` = '1'
            WHERE `enableGll` <> '1'
               OR `enableGll` IS NULL
        ");

        ($this->configWriter)(self::CONFIG_FLAG, '1');

        return $affectedRows;
    }

    public function buildControlMarkup(
        string $buttonLabel,
        string $pendingMessage,
        string $appliedMessage,
    ): string {
        $isApplied = $this->isApplied();
        $statusMessage = $isApplied ? $appliedMessage : $pendingMessage;
        $disabledAttribute = $isApplied ? ' disabled="disabled"' : '';

        return sprintf(
            '<div class="ls_shop_enableGllMigrationControl">' .
                '<p class="ls_shop_enableGllMigrationControl__status">%s</p>' .
                '<button type="submit" name="%s" value="1" class="tl_submit ls_shop_enableGllMigrationControl__button"%s>%s</button>' .
            '</div>',
            htmlspecialchars($statusMessage, ENT_QUOTES),
            self::SUBMIT_NAME,
            $disabledAttribute,
            htmlspecialchars($buttonLabel, ENT_QUOTES)
        );
    }
}
