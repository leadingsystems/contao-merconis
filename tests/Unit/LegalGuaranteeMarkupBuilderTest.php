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
        self::assertStringContainsString('aria-labelledby="merconis-legal-guarantee-svg-1-title"', $markup);
        self::assertStringContainsString('<title id="merconis-legal-guarantee-svg-1-title"', $markup);
        self::assertStringContainsString('Legal guarantee', $markup);
    }

    public function testBuildSvgMarkupRewritesFragmentIdsAndReferencesPerRender(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();
        $svgMarkup = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg">
  <defs>
    <clipPath id="clip-a">
      <rect id="shape-a" width="10" height="10" />
    </clipPath>
  </defs>
  <style>.label { clip-path: url(#clip-a); }</style>
  <g clip-path="url(#clip-a)" aria-describedby="shape-a">
    <use href="#shape-a" />
  </g>
</svg>
SVG;

        $firstMarkup = $builder->buildSvgMarkup($svgMarkup, 'Legal guarantee');
        $secondMarkup = $builder->buildSvgMarkup($svgMarkup, 'Legal guarantee');

        self::assertStringNotContainsString('id="clip-a"', $firstMarkup);
        self::assertStringNotContainsString('id="shape-a"', $firstMarkup);
        self::assertStringNotContainsString('.label { clip-path: url(#clip-a); }', $firstMarkup);
        self::assertStringNotContainsString('href="#shape-a"', $firstMarkup);
        self::assertMatchesRegularExpression('/id="merconis-legal-guarantee-svg-1-clip-a"/', $firstMarkup);
        self::assertStringContainsString(
            '.label { clip-path: url(#merconis-legal-guarantee-svg-1-clip-a); }',
            $firstMarkup
        );
        self::assertMatchesRegularExpression('/url\(#merconis-legal-guarantee-svg-1-clip-a\)/', $firstMarkup);
        self::assertMatchesRegularExpression('/href="#merconis-legal-guarantee-svg-1-shape-a"/', $firstMarkup);
        self::assertMatchesRegularExpression('/aria-describedby="merconis-legal-guarantee-svg-1-shape-a"/', $firstMarkup);
        self::assertMatchesRegularExpression('/id="merconis-legal-guarantee-svg-2-clip-a"/', $secondMarkup);
        self::assertStringNotContainsString('merconis-legal-guarantee-svg-1-clip-a', $secondMarkup);
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
