<?php

namespace LeadingSystems\MerconisBundle\ProductSearch\Enum;

enum Mode
{
    case Standard; // The standard searcher that works with the database directly
    case SearchServer; // This uses the search engine (most likely elasticsearch)
}
