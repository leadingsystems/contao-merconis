<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeMarkupBuilderTest extends TestCase
{
    public function testBuildSvgMarkupAddsAccessibilityAttributesAndTitle(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        $markup = $builder->buildSvgMarkup(
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>',
            'Legal guarantee'
        );

        self::assertStringContainsString('merconis-legal-guarantee-label', $markup);
        self::assertStringContainsString('role="img"', $markup);
        self::assertStringContainsString('aria-labelledby="merconis-legal-guarantee-title-', $markup);
        self::assertStringContainsString('<title id="merconis-legal-guarantee-title-', $markup);
        self::assertStringContainsString('Legal guarantee', $markup);
    }

    public function testBuildCheckoutGaranBlockReturnsEmptyStringWithoutAffectedItems(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        self::assertSame(
            '',
            $builder->buildCheckoutGaranBlock([], [
                'garanBlockHeadline' => 'Headline',
                'garanEuLinkText' => 'EU Link',
            ])
        );
    }

    public function testBuildCheckoutGllLinksContainsBothAnchors(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        $markup = $builder->buildCheckoutGllLinks(
            'legal-guarantee',
            'Gewährleistungslabel',
            'https://example.invalid/eu',
            ['gllEuLinkText' => 'EU-Information']
        );

        self::assertStringContainsString('href="legal-guarantee"', $markup);
        self::assertStringContainsString('Gewährleistungslabel', $markup);
        self::assertStringContainsString('href="https://example.invalid/eu"', $markup);
        self::assertStringContainsString('EU-Information', $markup);
    }
}
