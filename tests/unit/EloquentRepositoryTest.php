<?php
use Mockery as m;

class EloquentRepositoryTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		m::close();
	}

	public function testInitialize()
	{
		$m = $this->makeMockModel();
		$v = $this->makeMockValidator();
		$r = $this->makeRepo($m, $v);

		$this->assertInstanceOf('c\EloquentRepository', $r);
		$this->assertSame($m, $r->getModel());
	}

	public function testGetAll()
	{
		list($model, $validator, $repo) = $this->make();
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('get')->once()->andReturn('foo');

		$this->assertEquals('foo', $repo->getAll());
	}

	public function testGetAllPaginated()
	{
		list($model, $validator, $repo) = $this->make();
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('paginate')->once()->with(20)->andReturn('foo');

		$this->assertEquals('foo', $repo->paginate(20)->getAll());
	}

	public function testQueryBefore()
	{
		list($model, $validator, $repo) = $this->make('RepoWithBefores');
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('prepareQuery')->once();
		$query->shouldReceive('get')->once()->andReturn('foo');

		$this->assertEquals('foo', $repo->getAll());
	}

	public function testQueryAfter()
	{
		list($model, $validator, $repo) = $this->make('RepoWithAfters');
		$query = $this->makeMockQuery();
		$result = m::mock();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('paginate')->once()->andReturn($result);
		$result->shouldReceive('prepareResults')->once();

		$this->assertSame($result, $repo->paginate(20)->getAll());
	}

	public function testGetByKey()
	{
		list($model, $validator, $repo) = $this->make();
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('where->first')->once()->andReturn('foo');

		$this->assertEquals('foo', $repo->getByKey(1));
	}

	public function testFetchSinglePrepare()
	{
		list($model, $validator, $repo) = $this->make('RepoWithAfters');
		$query = $this->makeMockQuery();
		$result = m::mock();
		$model->shouldReceive('newQuery->where')->once()->andReturn($query);
		$query->shouldReceive('first')->once()->andReturn($result);
		$result->shouldReceive('prepareResults')->once();

		$this->assertSame($result, $repo->getByKey(1));
	}

	public function testInvalidCreate()
	{
		list($model, $validator, $repo) = $this->make('RepoWithBefores');
		$model->shouldReceive('newInstance')->once();
		$validator->shouldReceive('validCreate')->once()->andReturn(false);
		$validator->shouldReceive('errors->getMessages')->once()->andReturn([]);

		$this->assertFalse($repo->create(array()));
	}

	public function testCreate()
	{
		list($model, $validator, $repo) = $this->make();
		$model->shouldReceive('newInstance')->once()->andReturn($mock = m::mock(['fill->save' => true]));
		$validator->shouldReceive('validCreate')->once()->andReturn(true);
		$this->assertSame($mock, $repo->create(['foo' => 'bar']));
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testUpdateWhenNotExists()
	{
		list($model, $validator, $repo) = $this->make();
		$updateModel = new RepoTestModelStub;
		$updateModel->exists = false;

		$repo->update($updateModel, []);
	}

	public function testUpdateValidationFails()
	{
		list($model, $validator, $repo) = $this->make();
		$updateModel = new RepoTestModelStub;
		$updateModel->id = 'foo';
		$updateModel->exists = true;
		$validator->shouldReceive('replace')->once()->with('key', 'foo');
		$validator->shouldReceive('validUpdate')->once()->andReturn(false);
		$validator->shouldReceive('errors->getMessages')->once()->andReturn([]);

		$this->assertFalse($repo->update($updateModel, array()));
	}

	public function testUpdate()
	{
		list($model, $validator, $repo) = $this->make();
		$updateModel = $this->makeMockModel()->makePartial();
		$updateModel->id = 'foo';
		$updateModel->exists = true;
		$updateModel->shouldReceive('fill')->once()->with(['foo' => 'bar'])->andReturn(m::self());
		$updateModel->shouldReceive('save')->once()->andReturn(true);
		$validator->shouldReceive('replace')->once()->with('key', 'foo');
		$validator->shouldReceive('validUpdate')->once()->andReturn(true);

		$this->assertTrue($repo->update($updateModel, ['foo' => 'bar']));
	}

	public function testDelete()
	{
		list($model, $validator, $repo) = $this->make();
		$model = $this->makeMockModel();
		$model->shouldReceive('delete')->once()->andReturn(true);

		$this->assertTrue($repo->delete($model));
	}

	protected function make($class = 'RepoStub')
	{
		return [
			$m = $this->makeMockModel(),
			$v = $this->makeMockValidator(),
			$this->makeRepo($m, $v, $class),
		];
	}

	protected function makeRepo($model, $validator, $class = 'RepoStub')
	{
		return new $class($model, $validator);
	}

	public function makeMockModel($class = 'Illuminate\Database\Eloquent\Model')
	{
		$mock = m::mock($class);
		$mock->shouldReceive('getQualifiedKeyName')->andReturn('table.id');
		$mock->shouldReceive('getTable')->andReturn('table');
		return $mock;
	}

	public function makeMockValidator($class = 'c\Validator')
	{
		$mock = m::mock($class);
		$mock->shouldReceive('replace')->once()->with('table', 'table');
		return $mock;
	}

	public function makeMockQuery()
	{
		return m::mock('Illuminate\Database\Eloquent\Builder');
	}
}

class RepoStub extends \c\EloquentRepository {}

class RepoWithBefores extends \c\EloquentRepository
{
	protected function beforeQuery($query, $many)
	{
		$query->prepareQuery();
	}

	public function beforeCreate($model, $attributes)
	{
		$model->prepareModel();
	}

	public function beforeUpdate($model, $attributes)
	{
		$model->prepareModel();
	}
}

class RepoWithAfters extends \c\EloquentRepository
{
	public function afterQuery($results)
	{
		$results->prepareResults();
	}

	public function afterCreate($model)
	{
		$model->prepareModel();
	}

	public function afterUpdate($model)
	{
		$model->prepareModel();
	}
}

class RepoTestModelStub extends Illuminate\Database\Eloquent\Model
{

}
