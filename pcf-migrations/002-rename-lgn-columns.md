# Manuelle Migration 002: Rename LGN columns

**Erstellt:** 2026-11-18

## Kontext

Das Kürzel für die Mitteilung über das gesetzliche Gewährleistungsrecht heißt im Code
LGN statt GLL. Mit dem Code-Update tragen vier Datenbankspalten neue Namen in der DCA:

| Tabelle | Bisherige Spalte | Neue Spalte |
|---|---|---|
| `tl_ls_shop_product` | `gllMode` | `lgnMode` |
| `tl_member_group` | `lsShopGllMode` | `lsShopLgnMode` |
| `tl_ls_shop_orders` | `gllVersion` | `lgnVersion` |
| `tl_ls_shop_orders` | `gllLanguage` | `lgnLanguage` |

Contao erkennt eine Umbenennung nicht. Ohne diese Migration löscht `contao:migrate`
(ebenso das Datenbank-Update im Contao Manager oder im Install Tool) die bisherigen
Spalten und legt leere neue an. Die Einstellungen an Produkten und Mitgliedergruppen
und die Snapshot-Angaben der Bestellungen gingen verloren. Die Migration benennt die
Spalten deshalb mit ihren Werten um.

## POST-DEPLOY

Reihenfolge: Die Schritte laufen unmittelbar nach dem Code-Update und vor dem ersten
`contao:migrate`, vor einem Datenbank-Update im Contao Manager oder im Install Tool und
vor dem ersten Aufruf des Shops. Vor dem Code-Update dürfen die Spalten nicht
umbenannt werden, weil der bisherige Code die bisherigen Namen liest.

1. Datenbank sichern.
2. Voraussetzung prüfen: Die vier bisherigen Spalten sind vorhanden, die neuen noch
   nicht.

   ```sql
   SELECT TABLE_NAME, COLUMN_NAME
   FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND (TABLE_NAME, COLUMN_NAME) IN (
       ('tl_ls_shop_product', 'gllMode'),
       ('tl_member_group', 'lsShopGllMode'),
       ('tl_ls_shop_orders', 'gllVersion'),
       ('tl_ls_shop_orders', 'gllLanguage'),
       ('tl_ls_shop_product', 'lgnMode'),
       ('tl_member_group', 'lsShopLgnMode'),
       ('tl_ls_shop_orders', 'lgnVersion'),
       ('tl_ls_shop_orders', 'lgnLanguage')
     );
   ```

   - Genau die vier Zeilen mit `gllMode`, `lsShopGllMode`, `gllVersion` und
     `gllLanguage`: mit Schritt 3 fortfahren.
   - Keine Zeile: Die Installation hatte die bisherigen Spalten nie. Die Migration
     entfällt; `contao:migrate` legt die neuen Spalten an.
   - Jedes andere Ergebnis: nicht fortfahren und den Stand klären.
3. Werteverteilung vorher festhalten:

   ```sql
   SELECT gllMode, COUNT(*) FROM tl_ls_shop_product GROUP BY gllMode;
   SELECT lsShopGllMode, COUNT(*) FROM tl_member_group GROUP BY lsShopGllMode;
   SELECT gllVersion, COUNT(*) FROM tl_ls_shop_orders GROUP BY gllVersion;
   SELECT gllLanguage, COUNT(*) FROM tl_ls_shop_orders GROUP BY gllLanguage;
   ```

4. Spalten mit ihren Werten umbenennen. Die Definitionen entsprechen der neuen DCA:

   ```sql
   ALTER TABLE tl_ls_shop_product
     CHANGE gllMode lgnMode varchar(16) NOT NULL default 'inherit';
   ALTER TABLE tl_member_group
     CHANGE lsShopGllMode lsShopLgnMode varchar(16) NOT NULL default '';
   ALTER TABLE tl_ls_shop_orders
     CHANGE gllVersion lgnVersion varchar(16) NOT NULL default '',
     CHANGE gllLanguage lgnLanguage varchar(8) NOT NULL default '';
   ```

5. Caches leeren:
   `php vendor/bin/contao-console cache:clear` und
   `php vendor/bin/contao-console cache:clear --env=dev`
6. Danach erst `php vendor/bin/contao-console contao:migrate` beziehungsweise das
   Datenbank-Update im Contao Manager ausführen.

## Verifikation

1. `php vendor/bin/contao-console contao:migrate --dry-run --no-interaction` meldet
   für die vier Spalten weder `DROP` noch `ADD` und kein `CHANGE`.
2. Die Werteverteilung der neuen Spalten ist gleich der Verteilung aus Schritt 3:

   ```sql
   SELECT lgnMode, COUNT(*) FROM tl_ls_shop_product GROUP BY lgnMode;
   SELECT lsShopLgnMode, COUNT(*) FROM tl_member_group GROUP BY lsShopLgnMode;
   SELECT lgnVersion, COUNT(*) FROM tl_ls_shop_orders GROUP BY lgnVersion;
   SELECT lgnLanguage, COUNT(*) FROM tl_ls_shop_orders GROUP BY lgnLanguage;
   ```

3. Die Abfrage aus Schritt 2 liefert genau die vier Zeilen mit `lgnMode`,
   `lsShopLgnMode`, `lgnVersion` und `lgnLanguage`.
4. Die Caches für `prod` und `dev` sind geleert.

Gesendete Nachrichten aus der Zeit vor dem Update behalten ihre gespeicherten
Anhangspfade unter `bundles/leadingsystemsmerconis/legal-guarantee/gll/<version>/`.
Diese Pfade zeigen nach dem Update auf keine Datei mehr; es gibt keine parallele
Ablage unter `gll/`.
