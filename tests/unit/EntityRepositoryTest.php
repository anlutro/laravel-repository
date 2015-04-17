<?php
namespace anlutro\LaravelRepository\Tests;

use PHPUnit_Framework_TestCase;
use anlutro\LaravelRepository\EntityRepository;
use Mockery as m;

class EntityRepositoryTest extends PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		m::close();
	}

	protected function makeRepo($class)
	{
		$class = __NAMESPACE__.'\\'.$class;
		return new $class(m::mock('Illuminate\Database\Connection'));
	}

	/** @test */
	public function getNewReturnsEntityClassAndCallsDataMapper()
	{
		$repo = $this->makeRepo('StubEntityRepository');
		$entity = $repo->getNew(['foo' => 'bar']);
		$this->assertInstanceOf(__NAMESPACE__.'\\StubEntity', $entity);
		$this->assertEquals('bar', $entity->getFoo());
	}

	/** @test */
	public function dataMapperIsCalledOnWrite()
	{
		$repo = $this->makeRepo('StubEntityRepository');
		$entity = $repo->getNew(['foo' => 'bar']);
		$entity->setFoo('baz');
		$repo->getConnection()->shouldReceive('table->insertGetId')->once()->andReturn(1);
		$repo->persist($entity);
		$this->assertEquals(1, $entity->getKey());
	}
}

class StubEntity
{
	protected $id;
	protected $foo;
	public function getKey()
	{
		return $this->id;
	}
	public function setKey($id)
	{
		$this->id = $id;
	}
	public function getFoo()
	{
		return $this->foo;
	}
	public function setFoo($foo)
	{
		$this->foo = $foo;
	}
}

class StubDataMapper
{
	public function fill($entity, $attributes)
	{
		if (isset($attributes['foo'])) {
			$entity->setFoo(strtolower($attributes['foo']));
		}
	}

	public function map($entity)
	{
		return ['foo' => strtoupper($entity->getFoo())];
	}
}

class StubEntityRepository extends EntityRepository
{
	protected $table = 'table';
	protected $entityClass = 'anlutro\LaravelRepository\Tests\StubEntity';
	protected $dataMapperClass = 'anlutro\LaravelRepository\Tests\StubDataMapper';
}
