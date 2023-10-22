<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\StringUtil;
use Merconis\Core\ls_shop_generalHelper;

class GetPageLayoutListener
{
    public function getLayoutSettingsForGlobalUse(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        $GLOBALS['merconis_globals']['layoutID'] = $layout->id;
        $GLOBALS['merconis_globals']['layoutName'] = $layout->name;
        $GLOBALS['merconis_globals']['ls_shop_activateFilter'] = $layout->ls_shop_activateFilter;
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
}