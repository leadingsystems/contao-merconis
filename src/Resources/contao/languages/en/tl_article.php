<?php

$GLOBALS['TL_LANG']['tl_article']['lsShopOutputCondition']								= array('Condition');

$GLOBALS['TL_LANG']['tl_article']['lsShopConditionalOutput_legend']						= 'Conditional output (shop)';

$GLOBALS['TL_LANG']['tl_article']['lsShopOutputCondition']['options']					= array(
	'always' => array('Always display', 'The article is always displayed.'),
	'onlyIfNotOverview' => array('Only outside product overview', 'The article is only displayed in the product overview.'),
	'onlyInSingleview' => array('Only in product detail view', 'The article is only displayed in the product detail view.'),
	'onlyIfCartNotEmpty' => array('Only if shopping cart not empty', 'The article is only displayed if the shopping cart is not empty.'),
	'onlyIfCartEmpty' => array('Only if shopping cart empty', 'The article is only displayed if the shopping cart is empty.'),
    'onlyIfFeUserLoggedIn' => array('Only if front-end user is logged in', 'The article is only displayed if a front-end user (member/customer) is logged in.'),
    'onlyIfFeUserNotLoggedIn' => array('Only if no front-end user is logged in', 'The article is only displayed if a front-end user (member/customer) is logged in.')
);

$GLOBALS['TL_LANG']['tl_article']['lsShopProducerInfoText'] = array('Producer', 'Enter the name of the producer here where this information should be displayed, each producer only has one article');
$GLOBALS['TL_LANG']['tl_article']['lsShopIsProducerInfo'] = array('Producer information', 'Whether the article refers to the producer information or not');