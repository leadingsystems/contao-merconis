<?php

declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\SearchServer\Adapters\DirectMySQL\Index\Product\Clause;

final class ClauseBuildResult
{
	/** @var string|null */
	private $whereSql;

	/** @var array<int, string> */
	private array $scoreAdditions;

	/** @var array<string, mixed> */
	private array $params;

	/** @var array<string, int> */
	private array $paramTypes;

	/** @var array<int, string> */
	private array $debugSelects;

	public function __construct(?string $whereSql, array $scoreAdditions, array $params = [], array $paramTypes = [], array $debugSelects = [])
	{
		$this->whereSql = $whereSql;
		$this->scoreAdditions = $scoreAdditions;
		$this->params = $params;
		$this->paramTypes = $paramTypes;
		$this->debugSelects = $debugSelects;
	}

	public function getWhereSql(): ?string
	{
		return $this->whereSql;
	}

	/**
	 * @return array<int, string>
	 */
	public function getScoreAdditions(): array
	{
		return $this->scoreAdditions;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * @return array<string, int>
	 */
	public function getParamTypes(): array
	{
		return $this->paramTypes;
	}

	/**
	 * @return array<int, string>
	 */
	public function getDebugSelects(): array
	{
		return $this->debugSelects;
	}
}


