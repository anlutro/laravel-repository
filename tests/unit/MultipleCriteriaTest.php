<?php

use Mockery as m;

use anlutro\LaravelRepository\Criteria\MultipleCriteria;
use anlutro\LaravelRepository\Criteria\CallbackCriteria;

class MultipleCriteriaTest extends PHPUnit_Framework_TestCase
{
	private function makeCriteria($logic = MultipleCriteria::LOGIC_AND)
	{
		return new MultipleCriteria($logic);
	}

	private function makeCallbackCriteria(callable $callback)
	{
		return new CallbackCriteria($callback);
	}

	private function makeQuery()
	{
		$grammar = new Illuminate\Database\Query\Grammars\Grammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		return new Illuminate\Database\Query\Builder(
			m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
	}

	/** @test */
	public function adds_criteria_to_query()
	{
		$c = $this->makeCriteria();
		$c->push($this->makeCallbackCriteria(function($q) {
			$q->where('foo', 'bar')->orWhere('foo', 'baz');
		}));
		$c->push($this->makeCallbackCriteria(function($q) {
			$q->where('bar', 'baz')->orWhere('bar', 'foo');
		}));

		$c->apply($q = $this->makeQuery());

		$this->assertEquals('select * where ((("foo" = ? or "foo" = ?)) and (("bar" = ? or "bar" = ?)))', $q->toSql());
		$this->assertEquals(['bar', 'baz', 'baz', 'foo'], $q->getBindings());
	}
}
