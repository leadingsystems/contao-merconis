<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\Frontend\LegalGuaranteeMarkupBuilder;
use PHPUnit\Framework\TestCase;

final class LegalGuaranteeMarkupBuilderTest extends TestCase
{
    private const SVG_WITH_FRAGMENTS = <<<'SVG'
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

    public function testBuildSvgMarkupAddsAccessibilityAttributesAndTitleWithoutWrapper(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        $markup = $builder->buildSvgMarkup(
            '<svg xmlns="http://www.w3.org/2000/svg"><title>Old</title><rect width="10" height="10"/></svg>',
            'Legal guarantee',
            'lgn-10',
            ['lang' => 'de']
        );

        self::assertStringStartsWith('<svg', $markup);
        self::assertStringNotContainsString('<div', $markup);
        self::assertStringContainsString('role="img"', $markup);
        self::assertStringContainsString('aria-labelledby="merconis-legal-guarantee-svg-lgn-10-title"', $markup);
        self::assertStringContainsString('<title id="merconis-legal-guarantee-svg-lgn-10-title">Legal guarantee</title>', $markup);
        self::assertStringContainsString('lang="de"', $markup);
        self::assertSame(1, substr_count($markup, '<title'));
    }

    public function testBuildSvgMarkupRewritesFragmentIdsAndReferencesFromTypeAndReference(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        $markup = $builder->buildSvgMarkup(self::SVG_WITH_FRAGMENTS, 'Legal guarantee', 'garan-10');

        self::assertStringNotContainsString('id="clip-a"', $markup);
        self::assertStringNotContainsString('href="#shape-a"', $markup);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-10-clip-a"', $markup);
        self::assertStringContainsString(
            '.label { clip-path: url(#merconis-legal-guarantee-svg-garan-10-clip-a); }',
            $markup
        );
        self::assertStringContainsString('clip-path="url(#merconis-legal-guarantee-svg-garan-10-clip-a)"', $markup);
        self::assertStringContainsString('href="#merconis-legal-guarantee-svg-garan-10-shape-a"', $markup);
        self::assertStringContainsString('aria-describedby="merconis-legal-guarantee-svg-garan-10-shape-a"', $markup);
    }

    public function testBuildSvgMarkupAddsCounterOnlyWhenTheSameScopeRepeats(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        $firstMarkup = $builder->buildSvgMarkup(self::SVG_WITH_FRAGMENTS, 'Label', 'garan-10');
        $otherScopeMarkup = $builder->buildSvgMarkup(self::SVG_WITH_FRAGMENTS, 'Label', 'garan-20');
        $secondMarkup = $builder->buildSvgMarkup(self::SVG_WITH_FRAGMENTS, 'Label', 'garan-10');
        $thirdMarkup = $builder->buildSvgMarkup(self::SVG_WITH_FRAGMENTS, 'Label', 'garan-10');

        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-10-clip-a"', $firstMarkup);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-20-clip-a"', $otherScopeMarkup);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-10-2-clip-a"', $secondMarkup);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-10-3-clip-a"', $thirdMarkup);
        self::assertStringNotContainsString('merconis-legal-guarantee-svg-garan-10-clip-a', $secondMarkup);
    }

    public function testBuildDecorativeSvgMarkupHidesSvgFromScreenReadersWithoutTitle(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        $markup = $builder->buildDecorativeSvgMarkup(
            '<svg xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="t"><title id="t">Old</title><rect id="r" width="10" height="10"/><use href="#r"/></svg>',
            'garan-nested-10'
        );

        self::assertStringContainsString('aria-hidden="true"', $markup);
        self::assertStringContainsString('focusable="false"', $markup);
        self::assertStringNotContainsString('<title', $markup);
        self::assertStringNotContainsString('role=', $markup);
        self::assertStringNotContainsString('aria-labelledby', $markup);
        self::assertStringContainsString('id="merconis-legal-guarantee-svg-garan-nested-10-r"', $markup);
        self::assertStringContainsString('href="#merconis-legal-guarantee-svg-garan-nested-10-r"', $markup);
    }

    public function testReservePopoverIdBuildsIdFromTypeAndReferenceAndCountsRepetitions(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        self::assertSame('merconis-legal-guarantee-popover-lgn-10', $builder->reservePopoverId('lgn-10'));
        self::assertSame('merconis-legal-guarantee-popover-garan-10', $builder->reservePopoverId('garan-10'));
        self::assertSame('merconis-legal-guarantee-popover-lgn-checkout', $builder->reservePopoverId('lgn-checkout'));
        self::assertSame('merconis-legal-guarantee-popover-lgn-10-2', $builder->reservePopoverId('lgn-10'));
    }

    public function testPopoverIdsAndSvgPrefixesAreCountedIndependently(): void
    {
        $builder = new LegalGuaranteeMarkupBuilder();

        $builder->buildSvgMarkup(self::SVG_WITH_FRAGMENTS, 'Label', 'garan-10');

        self::assertSame('merconis-legal-guarantee-popover-garan-10', $builder->reservePopoverId('garan-10'));
    }
}
