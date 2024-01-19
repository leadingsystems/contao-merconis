<?php


namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\Database;
use Contao\Environment;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Merconis\Core\ls_shop_languageHelper;

/*
 * Diese Funktion wird beim Aufbauen des Suchindex aufgerufen und ergänzt das übergebene Array der in den Index aufzunehmenden Seiten/URLs
 * um die ebenfalls aufzunehmenden Produkt-Seiten/-URLs.
 */
class SitemapListener
{
    public function __invoke(SitemapEvent $event): void
    {

        $sitemap = $event->getDocument();
        $urlSet = $sitemap->childNodes[0];


        //für jede verfügbare Sprache im Shop eine alias_[SprachKey] Spalte erzeugen
        $arr_languageKeys = \Merconis\Core\ls_shop_languageHelper::getAllLanguages();

        $str_columns = '`pages`, `alias`';
        foreach ($arr_languageKeys as $str_languageKey) {
            $str_columns .= ', `alias_'.$str_languageKey.'`';
        }

        $objProducts = Database::getInstance()
            ->prepare("
			SELECT			".$str_columns."			
			FROM			`tl_ls_shop_product`
			WHERE			`published` = 1
		")
            ->limit(10000)
            ->execute();

        while ($objProducts->next()) {
            $whereConditionPages = '';
            $whereConditionValues = array();

            $objProducts->pages = StringUtil::deserialize($objProducts->pages);
            if (!is_array($objProducts->pages) || !count($objProducts->pages)) {
                continue;
            }
            foreach ($objProducts->pages as $page) {
                if ($whereConditionPages) {
                    $whereConditionPages .= ' OR ';
                }
                $whereConditionPages .= "`id` = ?";
                $whereConditionValues[] = $page;
            }
            if (!$whereConditionPages || !count($whereConditionValues)) {
                continue;
            }

            $time = time();
            $objPagesForProduct = Database::getInstance()->prepare("
					SELECT			id,
									alias
					FROM 			tl_page
					WHERE			(" . $whereConditionPages . ")
						AND			(start = '' OR start < " . $time . ")
						AND			(stop = '' OR stop > " . $time . ")
						AND			published = 1
						AND			noSearch != 1 AND sitemap!='map_never'"
            )
                ->execute($whereConditionValues);


            // Determine domain
            if (!$objPagesForProduct->numRows) {
                continue;
            } else {
                while ($objPagesForProduct->next()) {
                    $domain = Environment::get('base');
                    $arrLanguagePages = ls_shop_languageHelper::getLanguagePages($objPagesForProduct->id);
                    foreach ($arrLanguagePages as $languagePageInfo) {
                        $objPageForProduct = PageModel::findWithDetails($languagePageInfo['id']);

                        $str_languageAlias = $objProducts->{'alias_' . $objPageForProduct->language};
                        if ($str_languageAlias == '') {
                            continue;
                        }

                        $loc = $sitemap->createElement('loc');
                        $objRouter = System::getContainer()->get('router');
                        $frontendUrl = $objPageForProduct->getFrontendUrl('/product/' . $str_languageAlias/*, $objPageForProduct->language*/);

                        if(!(strpos($frontendUrl, "http://") === 0 || strpos($frontendUrl, "https://") === 0)){
                            $frontendUrl = (Environment::get('ssl') ? 'https://' : 'http://').$objRouter->getContext()->getHost()."/".$frontendUrl;
                        }

                        $loc->appendChild($sitemap->createTextNode($frontendUrl));

                        $urlEl = $sitemap->createElement('url');
                        $urlEl->appendChild($loc);
                        $urlSet->appendChild($urlEl);
                    }
                }
            }
        }
    }
}