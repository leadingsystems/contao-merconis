<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/*
 * Nicht `final`, damit Tests des Dashboard-Hinweises die Abfrage ohne Datenbank ersetzen können.
 */
class MemberGroupLgnModeInspector
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Liefert die Zahl der Mitgliedergruppen, deren `lsShopLgnMode` noch nicht festgelegt ist.
     * Ein gespeicherter Wert `NULL` zählt wie der leere Wert.
     *
     * @throws Exception Bei einem Fehler der Datenbankabfrage
     */
    public function countMemberGroupsWithoutLgnMode(): int
    {
        return (int) $this->connection->fetchOne("
            SELECT COUNT(*)
            FROM `tl_member_group`
            WHERE `lsShopLgnMode` = ''
               OR `lsShopLgnMode` IS NULL
        ");
    }
}
