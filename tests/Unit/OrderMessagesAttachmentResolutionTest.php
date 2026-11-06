<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Merconis\Core\ls_shop_orderMessages;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class OrderMessagesAttachmentResolutionTest extends TestCase
{
    public function testResolveAttachmentAbsolutePathKeepsFilesPathsUnderProjectRoot(): void
    {
        $resolvedPath = $this->invokeProtectedMethod(
            $this->createOrderMessagesInstance(),
            'resolveAttachmentAbsolutePath',
            [
                'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/test.pdf',
                '/tmp/merconis-project',
            ]
        );

        self::assertSame(
            '/tmp/merconis-project/files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/test.pdf',
            $resolvedPath
        );
    }

    public function testResolveAttachmentAbsolutePathPrefersPublicBundlePathWhenAvailable(): void
    {
        $projectDir = $this->createTempProjectDir();
        $expectedPath = $projectDir . '/public/bundles/leadingsystemsmerconis/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.pdf';
        mkdir(dirname($expectedPath), 0777, true);
        file_put_contents($expectedPath, 'pdf');

        $resolvedPath = $this->invokeProtectedMethod(
            $this->createOrderMessagesInstance(),
            'resolveAttachmentAbsolutePath',
            [
                'bundles/leadingsystemsmerconis/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.pdf',
                $projectDir,
            ]
        );

        self::assertSame($expectedPath, $resolvedPath);
    }

    public function testResolveAttachmentAbsolutePathFallsBackToVendorBundleSource(): void
    {
        $projectDir = $this->createTempProjectDir();
        $expectedPath = $projectDir . '/vendor/leadingsystems/contao-merconis/src/Resources/public/legal-guarantee/gll/v1.0/legal-guarantee-notice-en.pdf';
        mkdir(dirname($expectedPath), 0777, true);
        file_put_contents($expectedPath, 'pdf');

        $resolvedPath = $this->invokeProtectedMethod(
            $this->createOrderMessagesInstance(),
            'resolveAttachmentAbsolutePath',
            [
                'bundles/leadingsystemsmerconis/legal-guarantee/gll/v1.0/legal-guarantee-notice-en.pdf',
                $projectDir,
            ]
        );

        self::assertSame($expectedPath, $resolvedPath);
    }

    private function createOrderMessagesInstance(): ls_shop_orderMessages
    {
        $reflectionClass = new ReflectionClass(ls_shop_orderMessages::class);

        return $reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokeProtectedMethod(object $instance, string $methodName, array $arguments): mixed
    {
        $reflectionMethod = new ReflectionMethod($instance, $methodName);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($instance, $arguments);
    }

    private function createTempProjectDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/merconis-order-messages-' . uniqid('', true);
        mkdir($tempDir, 0777, true);

        return $tempDir;
    }
}
