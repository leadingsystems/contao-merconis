<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Environment;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\StringUtil;
use Contao\System;
use Merconis\Core\ls_shop_generalHelper;

class GetPageLayoutListener
{
    public function getLayoutSettingsForGlobalUse(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        $GLOBALS['merconis_globals']['layoutID'] = $layout->id;
        $GLOBALS['merconis_globals']['layoutName'] = $layout->name;
        $filterMode = (string) $layout->ls_shop_filterMode;
        $GLOBALS['merconis_globals']['ls_shop_activateFilter'] = ($filterMode !== '') ? '1' : '';
        $GLOBALS['merconis_globals']['ls_shop_useFastFilter'] = ($filterMode === 'fast') ? '1' : '';
        $GLOBALS['merconis_globals']['ls_shop_fastFilterAutoSubmit'] = $layout->ls_shop_fastFilterAutoSubmit;
        $GLOBALS['merconis_globals']['ls_shop_fastFilterHideZeroMatches'] = $layout->ls_shop_fastFilterHideZeroMatches;
        $GLOBALS['merconis_globals']['ls_shop_fastFilterResetMode'] = $layout->ls_shop_fastFilterResetMode;
        $GLOBALS['merconis_globals']['ls_shop_useFilterInStandardProductlist'] = $layout->ls_shop_useFilterInStandardProductlist;
        $GLOBALS['merconis_globals']['ls_shop_numFilterFieldsInSummary'] = $layout->ls_shop_numFilterFieldsInSummary;
        $GLOBALS['merconis_globals']['ls_shop_useFilterMatchEstimates'] = $layout->ls_shop_useFilterMatchEstimates;
        $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxNumProducts'] = $layout->ls_shop_matchEstimatesMaxNumProducts;
        $GLOBALS['merconis_globals']['ls_shop_matchEstimatesMaxFilterValues'] = $layout->ls_shop_matchEstimatesMaxFilterValues;
        $GLOBALS['merconis_globals']['ls_shop_useFilterInProductDetails'] = $layout->ls_shop_useFilterInProductDetails;
        $GLOBALS['merconis_globals']['ls_shop_hideFilterFormInProductDetails'] = $layout->ls_shop_hideFilterFormInProductDetails;

        $arr_themeData = ls_shop_generalHelper::ls_shop_getThemeDataForID($layout->pid);
        $GLOBALS['merconis_globals']['contaoThemeFolders'] = isset($arr_themeData) ? StringUtil::deserialize($arr_themeData['folders'], true) : array();

        $GLOBALS['merconis_globals']['int_rootPageId'] = $pageModel->rootId;
        $arr_pageData = ls_shop_generalHelper::ls_shop_getPageDataForID($pageModel->rootId);

        $GLOBALS['merconis_globals']['ls_shop_decimalsSeparator'] = $arr_pageData['ls_shop_decimalsSeparator'];
        $GLOBALS['merconis_globals']['ls_shop_thousandsSeparator'] = $arr_pageData['ls_shop_thousandsSeparator'];
        $GLOBALS['merconis_globals']['ls_shop_currencyBeforeValue'] = $arr_pageData['ls_shop_currencyBeforeValue'];
    }

    /*
     * This function checks if we are on a product detail page and if we are,
     * it checks if the page has different layout settings for the details view
     * and if it has, it overwrites the page's regular layout settings
     */
    public function switchTemplateInDetailsViewIfNecessary(PageModel &$pageModel, LayoutModel &$layout, PageRegular $pageRegular): void
    {
        if (!Input::get('product')) {
            /*
             * We don't have to deal with different layouts because we are
             * not on a product details page
             */
            return;
        }

        $int_layout = $pageModel->lsShopIncludeLayoutForDetailsView ? $pageModel->lsShopLayoutForDetailsView : false;

        if ($pageModel->type != 'root') {
            $int_pid = $pageModel->pid;
            $str_type = $pageModel->type;
            $objParentPage = PageModel::findParentsById($int_pid);

            if ($objParentPage !== null) {
                while ($int_pid > 0 && $str_type != 'root' && $objParentPage->next()) {
                    $int_pid = $objParentPage->pid;
                    $str_type = $objParentPage->type;

                    if ($objParentPage->lsShopIncludeLayoutForDetailsView) {
                        if ($int_layout === false) {
                            $int_layout = $objParentPage->lsShopLayoutForDetailsView;
                        }
                    }
                }
            }
        }

        if ($int_layout === false) {
            /*
             * We don't have to consider different layouts
             */
            return;
        }
        $pageModel->layout = $int_layout !== false ? $int_layout : $pageModel->layout;

        $layout = ls_shop_generalHelper::merconis_getPageLayout($pageModel);
    }


    public function onPageLoadRedirectUrl($page, $layout, $pageRegular): void
    {
        if (!empty($_SESSION['lsShop']['onPageLoadRedirectUrl'])) {

            $relativeUrl = (string) $_SESSION['lsShop']['onPageLoadRedirectUrl'];
            unset($_SESSION['lsShop']['onPageLoadRedirectUrl']);

            $relativeUrl = trim($relativeUrl);
            if ($relativeUrl !== '' && !str_starts_with($relativeUrl, '/')) {
                $relativeUrl = '/' . ltrim($relativeUrl, '/');
            }

            // Only allow relative URLs to prevent open redirects.
            if (
                $relativeUrl !== ''
                && str_starts_with($relativeUrl, '/')
                && !str_starts_with($relativeUrl, '//')
                && !str_contains($relativeUrl, "\r")
                && !str_contains($relativeUrl, "\n")
                && !str_contains($relativeUrl, "\\")
            ) {
                Controller::redirect(rtrim(Environment::get('base'), '/') . $relativeUrl);
            } else {
                System::getContainer()->get('monolog.logger.contao')->info(
                    'MERCONIS: Skipped on-page-load redirect because URL validation failed.',
                    [
                        'contao' => new ContaoContext('MERCONIS MESSAGES', TL_MERCONIS_ERROR),
                        'urlPreview' => substr($relativeUrl, 0, 120),
                    ]
                );
            }
        }
    }
}