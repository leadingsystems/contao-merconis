<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\Database;
use Contao\StringUtil;
use Contao\System;
use Merconis\Core\ls_shop_generalHelper;

#[AsInsertTag('shoppicture')]
#[AsInsertTag('shop_picture')]
class Picture extends InsertTag
{

	public function customInserttags($strTag, $params) {

        $params = $params[0];

        if (strpos($params, '?') !== false)
        {
            $arrChunks = explode('?', urldecode($params), 2);
            $strSource = StringUtil::decodeEntities($arrChunks[1]);
            $strSource = str_replace('[&]', '&', $strSource);
            $arrParams = explode('&', $strSource);

            foreach ($arrParams as $strParam)
            {
                list($key, $value) = explode('=', $strParam);

                switch ($key)
                {
                    case 'size':
                        /*
                         * Determine the image size id with the given merconis_alias and replace the alias with
                         * the id in the parameter string or remove the size parameter entirely if no image size
                         * record could be found with the merconis_alias.
                         */
                        $result = Database::getInstance()->prepare("SELECT id FROM tl_image_size WHERE merconis_alias=?")->execute($value)->fetchAssoc();
                        if ($result) {
                            $params = str_replace('size=' . $value, 'size=' . $result['id'], $params);
                        } else {
                            $params = ls_shop_generalHelper::removeGetParametersFromUrl($params, 'size');
                        }
                        break;
                }
            }
        }

        return System::getContainer()->get('contao.insert_tag.parser')->replace('{{picture::'.$params.'}}');

	}
}
