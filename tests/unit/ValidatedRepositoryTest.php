<?php
namespace anlutro\LaravelRepository\Tests;

use PHPUnit_Framework_TestCase;
use Mockery as m;

class ValidatedRepositoryTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		m::close();
	}

	protected function getRepo($class = null)
	{
		if (!$class) $class = 'ValidatedRepositoryStub';
		$class = __NAMESPACE__ . '\\' . $class;
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getTable')->once()->andReturn('table');
		$validator = m::mock('anlutro\LaravelValidation\ValidatorInterface');
		$validator->shouldReceive('replace')->once()->with('table', 'table');
		return new $class($model, $validator);
	}

	/** @test */
	public function errorsAreAddedAndCanBeRetrieved()
	{
		$repo = $this->getRepo();
		$repo->getModel()->shouldReceive('newInstance')->once();
		$repo->getValidator()->shouldReceive('valid')->once()->with('create', ['foo' => 'bar'])->andReturn(false);
		$repo->getValidator()->shouldReceive('getErrors')->once()->andReturn(new \Illuminate\Support\MessageBag(['error' => ['message']]));
		$repo->create(['foo' => 'bar']);
		$errors = $repo->getErrors();
		$this->assertInstanceOf('Illuminate\Support\MessageBag', $errors);
		$this->assertEquals(['error' => ['message']], $errors->getMessages());
	}

	/** @test */
	public function modelIsValidated()
	{
		$repo = $this->getRepo('ModelValidatedRepositoryStub');
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->exists = true;
		$model->shouldReceive('getKey')->once()->andReturn('1');
		$model->shouldReceive('getAttributes')->once()->andReturn(['raw_foo' => 'raw_bar']);
		$repo->getValidator()->shouldReceive('replace')->once()->with('key', '1')->andReturn(false);
		$repo->getValidator()->shouldReceive('valid')->once()->with('update', ['raw_foo' => 'raw_bar'])->andReturn(false);
		$repo->getValidator()->shouldReceive('getErrors')->once()->andReturn([]);
		$this->assertFalse($repo->update($model, ['foo' => 'bar']));
	}
}

class ValidatedRepositoryStub extends \anlutro\LaravelRepository\EloquentRepository {}
class ModelValidatedRepositoryStub extends \anlutro\LaravelRepository\EloquentRepository
{
	protected $validateEntity = true;
}
