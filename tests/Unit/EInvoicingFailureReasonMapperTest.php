<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use LeadingSystems\MerconisBundle\EInvoicing\Exception\EInvoicingGenerationException;
use LeadingSystems\MerconisBundle\EInvoicing\Exception\InvoiceValidationException;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingErrorCode;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReason;
use LeadingSystems\MerconisBundle\EInvoicing\Failure\EInvoicingFailureReasonMapper;
use PHPUnit\Framework\TestCase;

final class EInvoicingFailureReasonMapperTest extends TestCase
{
    public function testOwnValidationExceptionKeepsAllReasonsIndividually(): void
    {
        $mandateReferenceReason = new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_89, ['paymentMeansCode' => '59']);
        $debtorIbanReason = new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_91, ['paymentMeansCode' => '59']);

        $failureReasons = EInvoicingFailureReasonMapper::fromThrowable(
            new InvoiceValidationException([$mandateReferenceReason, $debtorIbanReason])
        );

        self::assertSame([$mandateReferenceReason, $debtorIbanReason], $failureReasons);
    }

    public function testOwnGenerationExceptionDeliversItsCodeAndParameters(): void
    {
        $failureReasons = EInvoicingFailureReasonMapper::fromThrowable(
            EInvoicingGenerationException::forReason(EInvoicingErrorCode::ORDER_NOT_FOUND, ['orderNumber' => '2026-404'])
        );

        self::assertCount(1, $failureReasons);
        self::assertSame(EInvoicingErrorCode::ORDER_NOT_FOUND, $failureReasons[0]->code);
        self::assertSame(['orderNumber' => '2026-404'], $failureReasons[0]->parameters);
    }

    public function testForeignExceptionWithMessageBecomesRuntimeErrorWithOriginalMessage(): void
    {
        $failureReasons = EInvoicingFailureReasonMapper::fromThrowable(new \RuntimeException('  mPDF error: font not found  '));

        self::assertCount(1, $failureReasons);
        self::assertSame(EInvoicingErrorCode::RUNTIME_ERROR, $failureReasons[0]->code);
        self::assertSame(['message' => 'mPDF error: font not found'], $failureReasons[0]->parameters);
    }

    public function testForeignThrowableWithoutMessageBecomesRuntimeErrorWithoutParameter(): void
    {
        foreach ([new \RuntimeException(''), new \TypeError('   ')] as $foreignThrowable) {
            $failureReasons = EInvoicingFailureReasonMapper::fromThrowable($foreignThrowable);

            self::assertCount(1, $failureReasons);
            self::assertSame(EInvoicingErrorCode::RUNTIME_ERROR, $failureReasons[0]->code);
            self::assertSame([], $failureReasons[0]->parameters);
        }
    }

    public function testExceptionMessageContainsOnlyTheCodes(): void
    {
        $validationException = new InvoiceValidationException([
            new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_27),
            new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_152, ['lineId' => '1']),
        ]);

        self::assertSame('EINVOICING_MISSING_BT_27, EINVOICING_MISSING_BT_152', $validationException->getMessage());
    }

    public function testExceptionRequiresAtLeastOneReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new InvoiceValidationException([]);
    }

    public function testFailureReasonRejectsParametersNotDeclaredInTheCatalog(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_27, ['lineId' => '1']);
    }

    public function testFailureReasonRejectsMissingRequiredParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EInvoicingFailureReason(EInvoicingErrorCode::MISSING_BT_152);
    }
}
