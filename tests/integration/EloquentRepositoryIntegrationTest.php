<?php
namespace anlutro\LaravelRepository\Tests;

use anlutro\LaravelTesting\EloquentTestCase;

class EloquentRepositoryIntegrationTest extends EloquentTestCase
{
	/**
	 * @test
	 */
	public function create()
	{
		$repo = $this->makeRepo();
		$model = $repo->create(['name' => 'foo', 'bool' => true]);
		$this->assertInstanceOf(__NAMESPACE__.'\ERIT_TestModel', $model);
		$this->assertEquals('foo', $model->name);
		$this->assertEquals(false, $model->bool);
		$this->assertTrue($model->exists);
	}

	/**
	 * @test
	 * @depends create
	 */
	public function getAll()
	{
		$repo = $this->makeRepo(); $model = $repo->create(['name' => 'foo']);
		$results = $repo->getAll();
		$this->assertEquals(1, $results->count());
		$this->assertEquals('foo', $results->first()->name);
	}

	/**
	 * @test
	 * @depends create
	 */
	public function findByKey()
	{
		$repo = $this->makeRepo(); $model = $repo->create(['name' => 'foo']);
		$result = $repo->findByKey($model->getKey());
		$this->assertEquals('foo', $result->name);
	}

	/**
	 * @test
	 * @depends create
	 */
	public function update()
	{
		$repo = $this->makeRepo(); $model = $repo->create(['name' => 'foo']);
		$repo->update($model, ['name' => 'bar']);
		$this->assertEquals('bar', $model->name);
	}

	/**
	 * @test
	 * @depends create
	 * @depends getAll
	 */
	public function delete()
	{
		$repo = $this->makeRepo(); $model = $repo->create(['name' => 'foo']);
		$this->assertTrue($repo->delete($model));
		$this->assertFalse($model->exists);
		$this->assertTrue($repo->getAll()->isEmpty());
	}

	/**
	 * @test
	 * @depends create
	 */
	public function customActions()
	{
		$repo = $this->makeRepo();
		$model = $repo->createAsAdmin(['name' => 'foo', 'bool' => true]);
		$this->assertInstanceOf(__NAMESPACE__.'\ERIT_TestModel', $model);
		$this->assertEquals('foo', $model->name);
		$this->assertEquals(true, $model->bool);
		$this->assertTrue($model->exists);

		$repo->updateAsAdmin($model, ['bool' => false]);
		$this->assertInstanceOf(__NAMESPACE__.'\ERIT_TestModel', $model);
		$this->assertEquals('foo', $model->name);
		$this->assertEquals(false, $model->bool);
		$this->assertTrue($model->exists);
	}

	/**
	 * @test
	 * @depends create
	 */
	public function advancedCustomAction()
	{
		$repo = $this->makeRepo(); $model = $repo->createAsAdmin(['name' => 'foo', 'bool' => true]);
		$repo->toggle($model);
		$this->assertEquals(false, $model->bool);
		$this->assertEquals(true, $model->beforeToggled);
		$this->assertEquals(true, $model->afterToggled);
	}

	protected function makeRepo()
	{
		return new ERIT_TestRepository(new ERIT_TestModel);
	}

	protected function getMigrations()
	{
		return [__NAMESPACE__.'\ERIT_TestMigration'];
	}
}

class ERIT_TestMigration extends \Illuminate\Database\Migrations\Migration
{
	public function up()
	{
		\Illuminate\Support\Facades\Schema::create('test_table', function($t) {
			$t->increments('id');
			$t->string('name');
			$t->boolean('bool')->default(false);
		});
	}

	public function down()
	{
		\Illuminate\Support\Facades\Schema::drop('test_table');
	}
}

class ERIT_TestRepository extends \anlutro\LaravelRepository\EloquentRepository
{
	public function __construct(ERIT_TestModel $model)
	{
		parent::__construct($model);
	}

	public function createAsAdmin(array $attributes)
	{
		$model = $this->getNew($attributes);
		$model->bool = array_get($attributes, 'bool');
		return $this->perform('create', $model, $attributes);
	}

	public function updateAsAdmin($model, array $attributes)
	{
		$model->bool = array_get($attributes, 'bool');
		return $this->perform('update', $model, $attributes);
	}

	public function toggle($model)
	{
		$attributes = $model->getAttributes();
		return $this->perform('toggle', $model, $attributes);
	}

	public function beforeToggle($model, array $attributes)
	{
		$model->beforeToggled = true;
	}

	public function performToggle($model, array $attributes)
	{
		$model->bool = !$model->bool;
		return $model;
	}

	public function afterToggle($model, array $attributes)
	{
		$model->afterToggled = true;
	}
}

class ERIT_TestModel extends \Illuminate\Database\Eloquent\Model
{
	public $timestamps = false;
	protected $fillable = ['name'];
	protected $table = 'test_table';
}
