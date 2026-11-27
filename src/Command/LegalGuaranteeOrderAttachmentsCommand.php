<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use LeadingSystems\MerconisBundle\LegalGuarantee\Order\OrderConfirmationAttachmentGenerator;
use Merconis\Core\ls_shop_generalHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LegalGuaranteeOrderAttachmentsCommand extends Command
{
    protected static $defaultName = 'merconis:legal-guarantee:order-attachments';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly OrderConfirmationAttachmentGenerator $attachmentGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Outputs legal guarantee attachment paths for a stored order.')
            ->addArgument('order-id', InputArgument::REQUIRED, 'The stored order ID.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderId = (int) $input->getArgument('order-id');
        $order = $this->loadOrder($orderId);

        if (null === $order) {
            $output->writeln('Order not found.');

            return Command::FAILURE;
        }

        $generatedAttachments = $this->attachmentGenerator->generateAttachments($order);

        foreach ($generatedAttachments['relativePaths'] as $relativePath) {
            $output->writeln($relativePath);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function loadOrder(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $this->framework->initialize();
        $order = ls_shop_generalHelper::getOrder($orderId, 'id', true);

        return is_array($order) ? $order : null;
    }
}
