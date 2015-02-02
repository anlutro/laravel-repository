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
 * Class that stores and applies a callback as a criteria.
 */
class MultipleCriteria implements CriteriaInterface
{

    /**
     * @var int
     */
    const LOGIC_AND = 1;

    /**
     * @var int
     */
    const LOGIC_OR = 2;

    /**
     * @var array
     */
    protected $criteria;

    /**
     * @param  \anlutro\LaravelRepository\CriteriaInterface $criteria
     * @param  int $condition
     * 
     * @return static
     *
     * @throws \InvalidArgumentException when $condition is unknown.
     */
    public function push(CriteriaInterface $criteria, $condition = self::LOGIC_AND)
    {
        if (($condition != static::LOGIC_AND) || ($condition != static::LOGIC_OR))
        {
            throw new \InvalidArgumentException('Unknown condition.');
        }

        $this->criteria[] = [$criteria, $condition];

        return $this;
    }

    /**
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     *
     * @return void
     */
    public function apply($query)
    {
        foreach ($this->criteria as $criteria)
        {
            $this->applyCriteria($query, $criteria);
        }
    }

    /**
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param array $criteria
     */
    protected function applyCriteria($query, $criteria)
    {
        if (static::LOGIC_AND === $criteria[1])
        {
            $this->applyAndCriteria($query, $criteria[0]);
        }
        else
        {
            $this->applyOrCriteria($query, $criteria[0]);
        }
    }

    /**
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param \anlutro\LaravelRepository\CriteriaInterface $criteria
     */
    protected function applyAndCriteria($query, $criteria)
    {
        $query->whereNested(function ($inner) use ($criteria)
        {
            $criteria->apply($inner);
        }, 'and');
    }

    /**
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param \anlutro\LaravelRepository\CriteriaInterface $criteria
     */
    protected function applyOrCriteria($query, $criteria)
    {
        $query->whereNested(function ($inner) use ($criteria)
        {
            $criteria->apply($inner);
        }, 'or');
    }

}
