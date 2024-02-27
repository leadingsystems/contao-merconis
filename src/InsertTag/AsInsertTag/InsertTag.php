<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsInsertTag;

use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;

abstract class InsertTag
{

    public function __invoke(ResolvedInsertTag $tag): InsertTagResult
    {
        return new InsertTagResult(
            $this->customInserttags($tag->getName(), $tag->getParameters()->all()),
            OutputType::html
        );
    }

    //should return the replacement for this insertTag
	public abstract function customInserttags($strTag, $params);
}
