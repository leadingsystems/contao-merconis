<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsBlockInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\System;

#[AsBlockInsertTag('shopiffeuserloggedin', endTag: 'shopiffeuserloggedin')]
#[AsBlockInsertTag('shopiffeusernotloggedin', endTag: 'shopiffeusernotloggedin')]

#[AsBlockInsertTag('shop_if_fe_user_logged_in', endTag: 'shop_if_fe_user_logged_in')]
#[AsBlockInsertTag('shop_if_fe_user_not_logged_in', endTag: 'shop_if_fe_user_not_logged_in')]
class IfFeUserLoggedIn extends BlockInsertTag
{
    public function __construct()
    {
        parent::__construct(['shopiffeusernotloggedin', 'shop_if_fe_user_not_logged_in']);
    }

    public function customInserttags($insertTag): bool
    {
        if(System::getContainer()->get('contao.security.token_checker')->hasFrontendUser()) {
            return true;
        }
        return false;
    }
}
