<?php
namespace anlutro\LaravelRepository\Tests;

use PHPUnit_Framework_TestCase;
use Mockery as m;

class Test extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		m::close();
	}

	/** @test */
	public function whereFiltersAreApplied()
	{
		$conn = $this->mockConnection();
		$repo = new DBRepoStub($conn);
		$repo->filterWhere('foo', 'bar');
		$conn->shouldReceive('table')->with('table')->once()->andReturn($query = $this->stubQuery($conn)->from('table'));
		$query->shouldReceive('get')->once()->andReturn(['foo']);
		$results = $repo->getAll();
		$this->assertEquals('select * from "table" where ("foo" = ?)', $query->toSql());
		$this->assertEquals(['bar'], $query->getBindings());
		$this->assertEquals(['foo'], $results);
	}

	public function mockConnection()
	{
		return m::mock('Illuminate\Database\Connection');
	}

	public function stubQuery($connection)
	{
		$grammar = new \Illuminate\Database\Query\Grammars\PostgresGrammar;
		$processor = new \Illuminate\Database\Query\Processors\PostgresProcessor;
		return m::mock('Illuminate\Database\Query\Builder', [$connection, $grammar, $processor])->makePartial();
	}
}

class FilterRepoStub extends \anlutro\LaravelRepository\DatabaseRepository
{
	protected $table = 'table';
}
