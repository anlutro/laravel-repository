<?php
use Mockery as m;

use Illuminate\Support\Fluent;

class DatabaseRepositoryTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		m::close();
	}

	public function testGetAll()
	{
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db, 'table');

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('get')->andReturn('foo');

		$this->assertEquals('foo', $repo->getAll());
	}

	public function testGetSingle()
	{
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db, 'table');

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('first')
			->andReturn('foo');

		$this->assertEquals('foo', $repo->getByKey(1));
	}

	public function testCreate()
	{
		$data = ['foo' => 'bar'];
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db, 'table');

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('insert')->with($data)->andReturn(true);

		$result = $repo->create($data);
		$this->assertEquals('bar', $result->foo);
	}

	public function testCreateFailure()
	{
		$data = ['foo' => 'bar'];
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db, 'table');

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('insert')->with($data)->andReturn(false);

		$this->assertFalse($repo->create($data));
	}

	public function testUpdate()
	{
		$model = $this->makeModel(['id' => 1]);
		$data = ['foo' => 'bar'];
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db, 'table');

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('update')
			->with(['id' => 1, 'foo' => 'bar'])->andReturn(true);

		$this->assertTrue($repo->update($model, $data));
	}

	public function testUpdateFailure()
	{
		$model = $this->makeModel(['id' => 1]);
		$data = ['foo' => 'bar'];
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db, 'table');

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('update')
			->with(['id' => 1, 'foo' => 'bar'])->andReturn(false);

		$this->assertFalse($repo->update($model, $data));
	}

	public function testDelete()
	{
		$model = $this->makeModel(['id' => 1]);
		$db = $this->mockConnection();
		$repo = new DBRepoStub($db, 'table');

		$query = $this->mockQuery();
		$db->shouldReceive('table')->with('table')->andReturn($query);
		$query->shouldReceive('where')->with('table.id', '=', 1)->once()
			->andReturn(m::self())->getMock()->shouldReceive('delete')
			->once()->andReturn(true);

		$this->assertTrue($repo->delete($model));
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

class DBRepoStub extends \c\DatabaseRepository
{
	protected $table = 'table';
}
