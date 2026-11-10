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
                'public',
            ]
        );

        self::assertSame(
            '/tmp/merconis-project/files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/test.pdf',
            $resolvedPath
        );
    }

    public function testResolveAttachmentAbsolutePathUsesConfiguredWebDirForBundlePaths(): void
    {
        $resolvedPath = $this->invokeProtectedMethod(
            $this->createOrderMessagesInstance(),
            'resolveAttachmentAbsolutePath',
            [
                'bundles/otherbundle/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.pdf',
                '/tmp/merconis-project',
                'web',
            ]
        );

        self::assertSame(
            '/tmp/merconis-project/web/bundles/otherbundle/legal-guarantee/gll/v1.0/legal-guarantee-notice-de.pdf',
            $resolvedPath
        );
    }

    public function testResolveAttachmentAbsolutePathRejectsAbsolutePaths(): void
    {
        $resolvedPath = $this->invokeProtectedMethod(
            $this->createOrderMessagesInstance(),
            'resolveAttachmentAbsolutePath',
            [
                '/tmp/forbidden.pdf',
                '/tmp/merconis-project',
                'public',
            ]
        );

        self::assertNull($resolvedPath);
    }

    public function testResolveExistingDynamicAttachmentPathsKeepsOnlyExistingPathsFromMultipleParseResults(): void
    {
        $temporaryProjectDirectory = sys_get_temp_dir() . '/merconis-order-messages-' . bin2hex(random_bytes(8));
        $existingRelativePath = 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/existing.pdf';
        $missingRelativePath = 'files/merconisfiles/dynamicAttachmentFiles/generatedFiles/garantielabels/missing.pdf';
        $existingAbsolutePath = $temporaryProjectDirectory . '/' . $existingRelativePath;

        mkdir(dirname($existingAbsolutePath), 0777, true);
        file_put_contents($existingAbsolutePath, 'test');

        try {
            $resolvedPaths = $this->invokeProtectedMethod(
                $this->createOrderMessagesInstance(),
                'resolveExistingDynamicAttachmentPaths',
                [
                    [$existingRelativePath, $missingRelativePath],
                    $temporaryProjectDirectory,
                    'public',
                ]
            );

            self::assertSame(
                [$existingRelativePath => $existingAbsolutePath],
                $resolvedPaths
            );
        } finally {
            unlink($existingAbsolutePath);
            rmdir(dirname($existingAbsolutePath));
            rmdir(dirname(dirname($existingAbsolutePath)));
            rmdir(dirname(dirname(dirname($existingAbsolutePath))));
            rmdir(dirname(dirname(dirname(dirname($existingAbsolutePath)))));
            rmdir(dirname(dirname(dirname(dirname(dirname($existingAbsolutePath))))));
        }
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

}
