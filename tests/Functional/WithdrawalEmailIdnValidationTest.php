<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Functional;

use Contao\Idna;
use Contao\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Verifiziert, dass IDN-E-Mail-Adressen die serverseitige Validierung
 * in Screen B und Screen C passieren.
 *
 * Beide Screens verwenden `Validator::isEmail($email)` als einzige
 * E-Mail-Formatpruefung (siehe `ModuleWithdrawal::validateScreenBForm`
 * und `ModuleWithdrawal::validateScreenCForm`). `Validator::isEmail()`
 * ruft intern `Idna::encodeEmail()` auf und konvertiert IDN-Domains
 * in Punycode, bevor die Regex-Pruefung erfolgt.
 */
final class WithdrawalEmailIdnValidationTest extends TestCase
{
    /**
     * @dataProvider idnEmailProvider
     */
    public function testIdnEmailIsAcceptedByServerSideValidation(string $email): void
    {
        self::assertTrue(
            Validator::isEmail($email),
            sprintf('IDN email "%s" must be accepted by Validator::isEmail()', $email)
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function idnEmailProvider(): iterable
    {
        yield 'IDN domain with umlaut' => ['user@müller.de'];
        yield 'IDN domain with eszett' => ['user@straße.de'];
        yield 'IDN subdomain' => ['user@mail.münchen.de'];
        yield 'IDN with numeric local part' => ['info123@bücher.de'];
    }

    /**
     * @dataProvider idnEmailPunycodeProvider
     */
    public function testIdnaEncodeEmailConvertsDomainToPunycode(
        string $unicodeEmail,
        string $expectedPunycode
    ): void {
        self::assertSame(
            $expectedPunycode,
            Idna::encodeEmail($unicodeEmail)
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function idnEmailPunycodeProvider(): iterable
    {
        yield 'müller.de' => [
            'user@müller.de',
            'user@xn--mller-kva.de',
        ];
    }

    public function testStandardEmailRemainsAccepted(): void
    {
        self::assertTrue(Validator::isEmail('user@example.com'));
    }

    public function testInvalidEmailIsRejected(): void
    {
        self::assertFalse(Validator::isEmail(''));
        self::assertFalse(Validator::isEmail('not-an-email'));
        self::assertFalse(Validator::isEmail('@missing-local.com'));
    }
}
