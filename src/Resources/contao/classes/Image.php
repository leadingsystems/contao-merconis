<?php

namespace Merconis\Core;

use Contao\FilesModel;
use Contao\System;

class Image
{

    private $filesModel;

    public function __construct($src)
    {
        $this->filesModel = FilesModel::findByPath(
            System::getContainer()
                ->get('kernel')
                ->getProjectDir() ."/".$src
        );
    }

    public function getMetadata($lang){

        return $this->filesModel->getMetadata($lang);
    }

    public function getAlt($lang){

        $metadata = $this->getMetadata($lang);

        if(!$metadata || !$metadata->getAlt()){
            return '';
        }
        return $metadata->getAlt();
    }

    public function getSrc(array $config){

        return System::getContainer()->get('contao.image.studio')
            ->createFigureBuilder()
            ->fromFilesModel($this->filesModel)
            ->setSize($config)
            ->build()
            ->getImage()
            ->getPicture()
            ->getImg()['src']
            ->getUrl(System::getContainer()->get('kernel')->getProjectDir());

    }

}