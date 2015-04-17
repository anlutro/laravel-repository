<?php
namespace anlutro\LaravelRepository\Tests;

use PHPUnit_Framework_TestCase;
use Mockery as m;

class DataMapperTest extends PHPUnit_Framework_TestCase
{
	/** @test */
	public function getterSetterMapper()
	{
		$mapper = new \anlutro\LaravelRepository\Mappers\GetterSetterMapper;
		$mapper->fill($entity = new MapperStubEntity, ['foo_attribute' => 'bar']);
		$this->assertEquals('bar', $entity->getFooAttribute());
		$this->assertEquals(['foo_attribute' => 'bar'], $mapper->map($entity));
	}

	/** @test */
	public function propertyMapper()
	{
		$mapper = new \anlutro\LaravelRepository\Mappers\PropertyMapper;
		$mapper->fill($entity = new MapperStubEntity, ['foo_property' => 'baz']);
		$this->assertEquals('baz', $entity->fooProperty);
		$this->assertEquals(['foo_property' => 'baz'], $mapper->map($entity));
	}

	/** @test */
	public function arrayAccessMapper()
	{
		$mapper = new \anlutro\LaravelRepository\Mappers\ArrayAccessMapper;
		$mapper->fill($entity = new ArrayAccessStub, ['foo' => 'baz']);
		$this->assertEquals('baz', $entity['foo']);
		$this->assertEquals(['foo' => 'baz'], $mapper->map($entity));
	}
}

class MapperStubEntity {
	protected $foo;
	public $fooProperty;
	public function getFooAttribute() {
		return $this->foo;
	}
	public function setFooAttribute($foo) {
		$this->foo = $foo;
	}
}

class ArrayAccessStub implements \ArrayAccess, \IteratorAggregate {
	protected $data;
	public function offsetExists($key) {
		return isset($this->data[$key]);
	}
	public function offsetGet($key) {
		return $this->data[$key];
	}
	public function offsetSet($key, $value) {
		$this->data[$key] = $value;
	}
	public function offsetUnset($key) {
		unset($this->data[$key]);
	}
	public function getIterator() {
		return new \ArrayIterator($this->data);
	}
}
