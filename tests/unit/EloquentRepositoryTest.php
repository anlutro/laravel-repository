<?php
namespace anlutro\LaravelRepository\Tests;

use Mockery as m;
use PHPUnit_Framework_TestCase;

class EloquentRepositoryTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		m::close();
	}

	/** @test */
	public function initialize()
	{
		$m = $this->makeMockModel();
		$v = $this->makeMockValidator();
		$r = $this->makeRepo($m, $v);

		$this->assertInstanceOf('anlutro\LaravelRepository\EloquentRepository', $r);
		$this->assertSame($m, $r->getModel());
	}

	/** @test */
	public function getAll()
	{
		list($model, $validator, $repo) = $this->make();
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('get')->once()->andReturn('foo');

		$this->assertEquals('foo', $repo->getAll());
	}

	/** @test */
	public function getAllPaginated()
	{
		list($model, $validator, $repo) = $this->make();
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('paginate')->once()->with(20)->andReturn('foo');

		$this->assertEquals('foo', $repo->paginate(20)->getAll());
	}

	/** @test */
	public function queryBefore()
	{
		list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithBefores');
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('doBeforeQueryStuff')->once();
		$query->shouldReceive('get')->once()->andReturn('foo');

		$this->assertEquals('foo', $repo->getAll());
	}

	/** @test */
	public function findBefore()
	{
		list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithBefores');
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('where')->with(m::any(),'=',10)->once()->andReturn(m::self());
		$query->shouldReceive('doBeforeQueryStuff')->once();
		$query->shouldReceive('first')->once()->andReturn('foo');

		$this->assertEquals('foo', $repo->findByKey(10));
	}

	/** @test */
	public function queryAfter()
	{
		list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithAfters');
		$query = $this->makeMockQuery();
		$result = m::mock();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('paginate')->once()->andReturn($result);
		$result->shouldReceive('prepareResults')->once();

		$this->assertSame($result, $repo->paginate(20)->getAll());
	}

	/** @test */
	public function findByKey()
	{
		list($model, $validator, $repo) = $this->make();
		$query = $this->makeMockQuery();
		$model->shouldReceive('newQuery')->once()->andReturn($query);
		$query->shouldReceive('where->first')->once()->andReturn('foo');

		$this->assertEquals('foo', $repo->findByKey(1));
	}

	/** @test */
	public function fetchSinglePrepare()
	{
		list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithAfters');
		$query = $this->makeMockQuery();
		$result = m::mock();
		$model->shouldReceive('newQuery->where')->once()->andReturn($query);
		$query->shouldReceive('first')->once()->andReturn($result);
		$result->shouldReceive('prepareResults')->once();

		$this->assertSame($result, $repo->findByKey(1));
	}

	/** @test */
	public function invalidCreate()
	{
		list($model, $validator, $repo) = $this->make(__NAMESPACE__.'\RepoWithBefores');
		$mockModel = $this->makeMockModel();
		$model->shouldReceive('newInstance')->once()->with([])->andReturn($mockModel);
		$validator->shouldReceive('valid')->once()->with('create', [])->andReturn(false);
		$validator->shouldReceive('getErrors')->once()->andReturn([]);

		$this->assertFalse($repo->create([]));
	}

	/** @test */
	public function create()
	{
		list($model, $validator, $repo) = $this->make();
		$model->shouldReceive('newInstance')->once()->with([])->andReturn($mock = m::mock());
		$mock->shouldReceive('fill')->once()->with(['foo' => 'bar']);
		$mock->shouldReceive('save')->once()->andReturn(true);
		$validator->shouldReceive('valid')->once()->with('create', ['foo' => 'bar'])->andReturn(true);
		$this->assertSame($mock, $repo->create(['foo' => 'bar']));
	}

	/** @test */
	public function updateWhenNotExists()
	{
		$this->setExpectedException('RuntimeException');
		list($model, $validator, $repo) = $this->make();
		$updateModel = new RepoTestModelStub;
		$updateModel->exists = false;

		$repo->update($updateModel, []);
	}

	/** @test */
	public function updateValidationFails()
	{
		list($model, $validator, $repo) = $this->make();
		$updateModel = new RepoTestModelStub;
		$updateModel->id = 'foo';
		$updateModel->exists = true;
		$validator->shouldReceive('replace')->once()->with('key', 'foo');
		$validator->shouldReceive('valid')->once()->with('update', [])->andReturn(false);
		$validator->shouldReceive('getErrors')->once()->andReturn([]);

		$this->assertFalse($repo->update($updateModel, []));
	}

	/** @test */
	public function update()
	{
		list($model, $validator, $repo) = $this->make();
		$updateModel = $this->makeMockModel()->makePartial();
		$updateModel->id = 'foo';
		$updateModel->exists = true;
		$updateModel->shouldReceive('fill')->once()->with(['foo' => 'bar'])->andReturn(m::self());
		$updateModel->shouldReceive('save')->once()->andReturn(true);
		$validator->shouldReceive('replace')->once()->with('key', 'foo');
		$validator->shouldReceive('valid')->once()->with('update', ['foo' => 'bar'])->andReturn(true);

		$this->assertTrue($repo->update($updateModel, ['foo' => 'bar']));
	}

	/** @test */
	public function delete()
	{
		list($model, $validator, $repo) = $this->make();
		$model = $this->makeMockModel();
		$model->shouldReceive('delete')->once()->andReturn(true);

		$this->assertTrue($repo->delete($model));
	}

	protected function make($class = null)
	{
		if (!$class) $class = __NAMESPACE__ . '\RepoStub';
		return [
			$m = $this->makeMockModel(),
			$v = $this->makeMockValidator(),
			$this->makeRepo($m, $v, $class),
		];
	}

	protected function makeRepo($model, $validator, $class = null)
	{
		if (!$class) $class = __NAMESPACE__ . '\RepoStub';
		return new $class($model, $validator);
	}

	public function makeMockModel($class = 'Illuminate\Database\Eloquent\Model')
	{
		$mock = m::mock($class);
		$mock->shouldReceive('getQualifiedKeyName')->andReturn('table.id');
		$mock->shouldReceive('getTable')->andReturn('table');
		return $mock;
	}

	public function makeMockValidator($class = 'anlutro\LaravelValidation\ValidatorInterface')
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

class RepoStub extends \anlutro\LaravelRepository\EloquentRepository {}

class RepoWithBefores extends \anlutro\LaravelRepository\EloquentRepository
{
	protected function beforeQuery($query, $many)
	{
		$query->doBeforeQueryStuff();
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

class RepoWithAfters extends \anlutro\LaravelRepository\EloquentRepository
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

class RepoTestModelStub extends \Illuminate\Database\Eloquent\Model
{

}
