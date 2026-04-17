<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Tests\Unit;

use Contao\DataContainer;
use PHPUnit\Framework\TestCase;

final class MessageModelCustomerDataTypeDefaultsTest extends TestCase
{
    public function testCustomerDataTypeOptionsAreLimitedToWithdrawalDataForWithdrawalType(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(true);

        self::assertSame(
            ['withdrawalData'],
            $controller->getCustomerDataTypeOptions($this->createMock(DataContainer::class))
        );
    }

    public function testCustomerDataTypeOptionsExcludeWithdrawalDataForNonWithdrawalType(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(false);

        self::assertSame(
            ['personalData', 'paymentData', 'shippingData'],
            $controller->getCustomerDataTypeOptions($this->createMock(DataContainer::class))
        );
    }

    public function testCustomerDataType1DefaultIsWithdrawalDataForWithdrawalTypeOnEmptyValue(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(true);

        self::assertSame(
            'withdrawalData',
            $controller->setCustomerDataType1Default('', $this->createMock(DataContainer::class))
        );
    }

    public function testCustomerDataType1DefaultIsPersonalDataForNonWithdrawalTypeOnEmptyValue(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(false);

        self::assertSame(
            'personalData',
            $controller->setCustomerDataType1Default('', $this->createMock(DataContainer::class))
        );
    }

    public function testCustomerDataType1KeepsExistingValueUnchanged(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(true);

        self::assertSame(
            'paymentData',
            $controller->setCustomerDataType1Default('paymentData', $this->createMock(DataContainer::class))
        );
    }

    public function testSaveCustomerDataType1DefaultIsWithdrawalDataForWithdrawalTypeOnEmptyValue(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(true);

        self::assertSame(
            'withdrawalData',
            $controller->saveCustomerDataType1Default('', $this->createMock(DataContainer::class))
        );
    }

    public function testSaveCustomerDataType1DefaultIsPersonalDataForNonWithdrawalTypeOnEmptyValue(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(false);

        self::assertSame(
            'personalData',
            $controller->saveCustomerDataType1Default('', $this->createMock(DataContainer::class))
        );
    }

    public function testSaveCustomerDataType1KeepsExistingValueUnchanged(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(true);

        self::assertSame(
            'paymentData',
            $controller->saveCustomerDataType1Default('paymentData', $this->createMock(DataContainer::class))
        );
    }

    public function testCustomerDataType2DefaultRemainsWithdrawalDataForWithdrawalTypeOnEmptyValue(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(true);

        self::assertSame(
            'withdrawalData',
            $controller->setCustomerDataType2Default('', $this->createMock(DataContainer::class))
        );
    }

    public function testSaveCustomerDataType2DefaultRemainsWithdrawalDataForWithdrawalTypeOnEmptyValue(): void
    {
        $controller = $this->createControllerWithWithdrawalFlag(true);

        self::assertSame(
            'withdrawalData',
            $controller->saveCustomerDataType2Default('', $this->createMock(DataContainer::class))
        );
    }

    private function createControllerWithWithdrawalFlag(bool $isWithdrawalType): object
    {
        $this->loadMessageModelControllerClass();

        return new class ($isWithdrawalType) extends \Merconis\Core\tl_ls_shop_message_model_controller {
            public function __construct(private bool $isWithdrawalType)
            {
            }

            protected function isWithdrawalMessageType(DataContainer $dc): bool
            {
                return $this->isWithdrawalType;
            }
        };
    }

    private function loadMessageModelControllerClass(): void
    {
        if (class_exists(\Merconis\Core\tl_ls_shop_message_model_controller::class, false)) {
            return;
        }

        $loader = new class {
            public function getTemplateGroup(string $prefix): array
            {
                return [];
            }

            public function load(string $filePath): void
            {
                require_once $filePath;
            }
        };

        $loader->load(__DIR__ . '/../../src/Resources/contao/dca/tl_ls_shop_message_model.php');
    }
}
