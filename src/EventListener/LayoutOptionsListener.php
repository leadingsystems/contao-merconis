<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @Callback(table="tl_page", target="fields.lsShopLayoutForDetailsView.options")
 */
class LayoutOptionsListener implements ResetInterface
{
    private Connection $connection;
    private ?array $options = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(): array
    {
        if (null === $this->options) {
            $this->options = [];
            $layouts = $this->connection->fetchAllAssociative('SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id ORDER BY t.name, l.name');

            foreach ($layouts as $layout) {
                $this->options[$layout['theme']][$layout['id']] = $layout['name'];
            }
        }

        return $this->options;
    }

    public function reset(): void
    {
        $this->options = null;
    }
}
