<?php
namespace anlutro\LaravelRepository\Tests;

use Illuminate\Support\Fluent;
use Mockery as m;
use PHPUnit_Framework_TestCase;

class DatabaseRepositoryTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		m::close();
	}

	/** @test */
	public function getAll()
	{
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('get')->andReturn('foo');

		$this->assertEquals('foo', $repo->getAll());
	}

	/** @test */
	public function getSingle()
	{
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('first')
			->andReturn('foo');

		$this->assertEquals('foo', $repo->findByKey(1));
	}

	/** @test */
	public function create()
	{
		$data = ['foo' => 'bar'];
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('insert')->with($data)->andReturn(true);

		$result = $repo->create($data);
		$this->assertEquals('bar', $result->foo);
	}

	/** @test */
	public function createFailure()
	{
		$data = ['foo' => 'bar'];
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('insert')->with($data)->andReturn(false);

		$this->assertFalse($repo->create($data));
	}

	/** @test */
	public function update()
	{
		$model = $this->makeModel(['id' => 1]);
		$data = ['foo' => 'bar'];
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('update')
			->with(['id' => 1, 'foo' => 'bar'])->andReturn(true);

		$this->assertTrue($repo->update($model, $data));
	}

	/** @test */
	public function updateFailure()
	{
		$model = $this->makeModel(['id' => 1]);
		$data = ['foo' => 'bar'];
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('update')
			->with(['id' => 1, 'foo' => 'bar'])->andReturn(false);

		$this->assertFalse($repo->update($model, $data));
	}

	/** @test */
	public function delete()
	{
		$model = $this->makeModel(['id' => 1]);
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('delete')
			->once()->andReturn(true);

		$this->assertTrue($repo->delete($model));
	}

	/** @test */
	public function throwExceptionsOnFailedFind()
	{
		$this->setExpectedException('anlutro\LaravelRepository\NotFoundException');
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);
		$repo->toggleExceptions(true);
		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('first')
			->once()->andReturn(null);
		$repo->findByKey(1);
	}

	/** @test */
	public function criteriaIsApplied()
	{
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db);

		$repo->pushCriteria(new CriteriaStub);
		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('applyCriteria')->once();
		$query->shouldReceive('get')->once();

		$repo->getAll();
	}

	/** @test */
	public function DefaultCiteriaAreApplied()
	{
		$db = $this->mockConnection();
		$repo = new DefaultCriteriaStub($db);

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('applyCriteria')->once();
		$query->shouldReceive('get')->once();

		$repo->getAll();
	}

	public function makeModel(array $attributes)
	{
		return new Fluent($attributes);
	}

	public function mockConnection()
	{
		return m::mock('Illuminate\Database\Connection');
	}

	public function mockQuery()
	{
		return m::mock('Illuminate\Database\Query\Builder');
	}
}

class DBRepoStub extends \anlutro\LaravelRepository\DatabaseRepository
{
	protected $table = 'table';
}

class DefaultCriteriaStub extends DBRepoStub
{
	protected $defaultCriteria = [
		'anlutro\LaravelRepository\Tests\CriteriaStub'
	];
}

class CriteriaStub implements \anlutro\LaravelRepository\CriteriaInterface
{
	public function apply($query)
	{
		$query->applyCriteria();
	}
}
