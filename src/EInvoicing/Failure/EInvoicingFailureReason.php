<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EInvoicing\Failure;

use InvalidArgumentException;

/**
 * Strukturierter Fehlergrund: Fehlercode plus optionale Parameter.
 */
final class EInvoicingFailureReason
{
    /**
     * @param array<string, string> $parameters
     *
     * @throws InvalidArgumentException Wenn die Parameter nicht zu den im Katalog deklarierten passen
     */
    public function __construct(
        public readonly EInvoicingErrorCode $code,
        public readonly array $parameters = [],
    ) {
        $expectedParameterNames = $code->parameterNames();
        $givenParameterNames = array_keys($parameters);
        sort($expectedParameterNames);
        sort($givenParameterNames);

        $parametersOmittedAllowed = $givenParameterNames === [] && $code->hasOptionalParameters();

        if ($givenParameterNames !== $expectedParameterNames && !$parametersOmittedAllowed) {
            throw new InvalidArgumentException(sprintf(
                'Failure reason %s expects the parameters [%s], got [%s].',
                $code->value,
                implode(', ', $expectedParameterNames),
                implode(', ', $givenParameterNames)
            ));
        }
    }
}
