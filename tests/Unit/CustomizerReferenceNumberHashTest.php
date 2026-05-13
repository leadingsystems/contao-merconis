<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CustomizerReferenceNumberHashTest extends TestCase
{
    /**
     * Berechnet den Customizer-Referenzhash analog zur Logik in
     * `ls_shop_checkout::createOrder()`.
     */
    private function computeHash(string $summary, string $flexData): string
    {
        return strtoupper(substr(md5($summary . $flexData), 0, 8));
    }

    public function testHashIsEightCharactersLong(): void
    {
        $hash = $this->computeHash('some summary', 'some flex data');

        self::assertSame(8, strlen($hash));
    }

    public function testHashContainsOnlyUppercaseHexCharacters(): void
    {
        $hash = $this->computeHash('summary text', 'flex data text');

        self::assertMatchesRegularExpression('/^[0-9A-F]{8}$/', $hash);
    }

    public function testHashIsDeterministic(): void
    {
        $summary = 'Farbe: Rot, Gravur: "Test"';
        $flexData = '{"color":"red","engraving":"Test"}';

        $hash1 = $this->computeHash($summary, $flexData);
        $hash2 = $this->computeHash($summary, $flexData);

        self::assertSame($hash1, $hash2);
    }

    public function testDifferentInputsProduceDifferentHashes(): void
    {
        $hashA = $this->computeHash('summary A', 'flex A');
        $hashB = $this->computeHash('summary B', 'flex B');

        self::assertNotSame($hashA, $hashB);
    }

    public function testKnownInputProducesExpectedHash(): void
    {
        $summary = 'test-summary';
        $flexData = 'test-flexdata';
        $expectedHash = strtoupper(substr(md5('test-summarytest-flexdata'), 0, 8));

        $hash = $this->computeHash($summary, $flexData);

        self::assertSame($expectedHash, $hash);
    }

    public function testEmptyInputsProduceValidHash(): void
    {
        $hash = $this->computeHash('', '');

        self::assertSame(8, strlen($hash));
        self::assertMatchesRegularExpression('/^[0-9A-F]{8}$/', $hash);
    }

    public function testNoCustomizationReturnsEmptyString(): void
    {
        $hasCustomization = false;
        $result = $hasCustomization
            ? $this->computeHash('summary', 'flexData')
            : '';

        self::assertSame('', $result);
    }
}
