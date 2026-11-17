<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\LegalGuarantee\MemberGroup;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/*
 * Nicht `final`, damit Tests des Dashboard-Hinweises die Abfrage ohne Datenbank ersetzen können.
 */
class MemberGroupGllModeInspector
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Liefert die Zahl der Mitgliedergruppen, deren `lsShopGllMode` noch nicht festgelegt ist.
     * Ein gespeicherter Wert `NULL` zählt wie der leere Wert.
     *
     * @throws Exception Bei einem Fehler der Datenbankabfrage
     */
    public function countMemberGroupsWithoutGllMode(): int
    {
        return (int) $this->connection->fetchOne("
            SELECT COUNT(*)
            FROM `tl_member_group`
            WHERE `lsShopGllMode` = ''
               OR `lsShopGllMode` IS NULL
        ");
    }
}
