<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\LegalGuarantee\OfficialGuaranteeLanguageResolver;
use PHPUnit\Framework\TestCase;

final class OfficialGuaranteeLanguageResolverTest extends TestCase
{
    public function testResolvesLanguageFromContaoLocalePrefix(): void
    {
        $resolver = new OfficialGuaranteeLanguageResolver();

        self::assertSame(
            'de',
            $resolver->resolveOfficialLanguage('de_AT', ['de', 'en', 'fr'])
        );
        self::assertSame(
            'pt',
            $resolver->resolveOfficialLanguage('pt-BR', ['en', 'pt'])
        );
    }

    public function testFallsBackToEnglishWhenLocaleIsUnavailable(): void
    {
        $resolver = new OfficialGuaranteeLanguageResolver();

        self::assertSame(
            'en',
            $resolver->resolveOfficialLanguage('lv_LV', ['de', 'en', 'fr'])
        );
    }

    public function testFallsBackToFirstAvailableLanguageWhenEnglishIsMissing(): void
    {
        $resolver = new OfficialGuaranteeLanguageResolver();

        self::assertSame(
            'de',
            $resolver->resolveOfficialLanguage('lv_LV', ['de', 'fr'])
        );
    }
}
