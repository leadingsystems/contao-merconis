<?php

namespace LeadingSystems\MerconisBundle\InsertTag\AsBlockInsertTag;

use Contao\CoreBundle\InsertTag\ParsedSequence;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\BlockInsertTagResolverNestedResolvedInterface;

abstract class BlockInsertTag implements BlockInsertTagResolverNestedResolvedInterface
{

    // $arrNot is the negativ value
    public function __construct( $arrNot)
    {
        $this->arrNot = $arrNot;
    }

    private function isNegativ($insertTag): bool
    {
        if (in_array(
            $insertTag->getName(),
            $this->arrNot
        )) {
            return true;
        }
        return false;
    }

    public function __invoke(ResolvedInsertTag $insertTag, ParsedSequence $wrappedContent): ParsedSequence
    {
        $inverse = $this->isNegativ($insertTag);

        if ($this->customInserttags($insertTag)) {
            return $inverse ? new ParsedSequence([]) : $wrappedContent;
        }

        return $inverse ? $wrappedContent : new ParsedSequence([]);
    }


    //should return the replacement for this insertTag
	abstract function customInserttags($insertTag);
}
