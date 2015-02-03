<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository\Criteria;

use anlutro\LaravelRepository\CriteriaInterface;

/**
 * Class that stores more criteria with logic conditions.
 *
 * @author  Bradley Weston <b.weston@outlook.com>
 */
class MultipleCriteria implements CriteriaInterface
{
	const LOGIC_AND = 1;
	const LOGIC_OR = 2;

	/**
	 * @var int
	 */
	protected $logic;

	/**
	 * @var CriteriaInterface[]
	 */
	protected $criteria = [];

	/**
	 * Constructor.
	 *
	 * @param  int $logic
	 */
	public function __construct($logic = self::LOGIC_AND)
	{
		if ($logic !== static::LOGIC_AND && $logic !== static::LOGIC_OR) {
			throw new \InvalidArgumentException('Unknown logic argument: '.$logic);
		}

		$this->logic = $logic;
	}

	/**
	 * Add a criteria.
	 *
	 * @param  CriteriaInterface $criteria
	 *
	 * @return $this
	 */
	public function push(CriteriaInterface $criteria)
	{
		$this->criteria[] = $criteria;

		return $this;
	}

	/**
	 * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
	 *
	 * @return void
	 */
	public function apply($query)
	{
		$query->where(function($query) {
			$logic = ($this->logic === static::LOGIC_AND) ? 'and' : 'or';

			foreach ($this->criteria as $criteria) {
				$query->whereNested(function ($query) use ($criteria) {
					$criteria->apply($query);
				}, $logic);
			}
		});
	}
}
