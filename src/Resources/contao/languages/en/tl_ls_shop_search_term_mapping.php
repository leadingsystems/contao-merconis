<?php

$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['sourceTerm'] = ['Source term', 'User input token to map'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['sourceNormalized'] = ['Source (normalized)', 'Lowercased source term for uniqueness'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['targetTerm'] = ['Target term', 'Token to append to search input'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['removeSource'] = ['Remove source term', 'If enabled, the source token will be removed from the query and only the target token will be used'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['active'] = ['Active', 'Enable or disable this mapping'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['matchType'] = ['Match type', 'Choose whether to match an exact token or a regex pattern'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['pattern'] = ['Pattern', 'PCRE pattern without delimiters; applied to each token'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['caseInsensitive'] = ['Case-insensitive', 'Apply case-insensitive matching for this pattern'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['title'] = ['Title', 'Optional label shown in the list (overrides generated label).'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['sorting'] = ['Sorting', 'Numeric order for applying mappings; lower numbers run first.'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['removeSourceTiming'] = ['Remove source timing', 'When to remove the source token (only for regex rules)'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['removeSourceTiming_options']['after'] = 'After applying all rules';
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['removeSourceTiming_options']['immediate'] = 'Immediately after this rule';

$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['mode'] = ['Mode', 'Limit this mapping to Quick search, Full search, or both'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['mode_options']['both'] = 'Both (Quick & Full)';
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['mode_options']['full'] = 'Full search only';
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['mode_options']['quick'] = 'Quick search only';

$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['nodeType'] = [
    'Type',
    'Two-level tree: Top level may contain Groups or standalone Mappings. '
    . 'Groups cannot have a parent and Mappings cannot have children. '
    . 'Use drag-and-drop to reorder: the order of Groups determines the processing order of their child Mappings; '
    . 'within a Group, child Mappings are processed in their own sorting order.'
];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['nodeType_options']['group'] = 'Group';
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['nodeType_options']['mapping'] = 'Mapping';
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['defaultGroupTitle'] = 'Group';

$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['edit'] = ['Edit mapping', 'Edit mapping ID %s'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['copy'] = ['Duplicate mapping', 'Duplicate mapping ID %s'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['delete'] = ['Delete mapping', 'Delete mapping ID %s'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['toggle'] = ['Toggle active', 'Activate/deactivate mapping ID %s'];
$GLOBALS['TL_LANG']['tl_ls_shop_search_term_mapping']['show'] = ['Details', 'Show details of mapping ID %s'];

$GLOBALS['TL_LANG']['MOD']['ls_shop_search_term_mapping'] = ['Search term mappings', 'Configure term→term mappings for search augmentation'];


