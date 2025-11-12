<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

final class PublishedConstraintBuilder
{
	public function build($published): ?string
	{
		if (!isset($published)) {
			return null;
		}
		if ($published === '1' || $published === 1 || $published === true) {
			return 'product.published = 1';
		}
		return null;
	}
}


