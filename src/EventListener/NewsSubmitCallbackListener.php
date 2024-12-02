<?php
/*
namespace LeadingSystems\MerconisBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_article', target: 'config.onsubmit')]
class NewsSubmitCallbackListener
{
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $this->db->update('tl_news', ['foobar' => 'foo'], ['id' => $dc->id]);
    }
}*/