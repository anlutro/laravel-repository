# Repositories

This package comes with two repository versions - database and eloquent. The database one works using the query builder, the eloquent one using models. This document contains information that applies to both versions.

## What is a repository?

A repository is a class that lies somewhere between the controller and the database logic to make your application's service layers (including controllers) more lightweight and allows you to more easily re-use database logic in your application.

A pure repository implementation means that it is the gateway from your application logic to the database - only the repository knows how to read/write from/to the database, the rest of your application has to go through a repository to achieve persistance.

## Basic usage

The repository classes come with some standard methods for common operations out of the box. First of all some terminology.

Methods starting with "find" is for fetching a single row in the database. Any method calling find will return an object representing a single row or null if it is not found.

Methods starting with "get" is for fetching multiple rows. It will always return an array or array-like object, which may or may not be empty.

### Query methods

- findByKey($key)
- findByAttributes(array $attributes)
- getAll()
- getByAttributes(array $attributes)
- getList($column = 'id', $key = null) - the same as $query->lists() in Laravel

### Persistance methods

- create(array $attributes)
- update(object $entity, array $attributes)
- delete(object $entity)

### Other public methods

- getNew(array $attributes) - gets a new entity object
- toggleExceptions(boolean $toggle = true)
- paginate($toggle) - switches pagination on or off. Pass an integer to turn on and set the number of rows per page, or `false` to disable.

### Protected methods

- newQuery() - instantiate a new query builder
- fetchSingle($query) - fetch the first row from a query builder
- fetchMany($query) - fetch all the rows from a query builder
- fetchList($query, $column = 'id', $key = null) - perform a lists() call on a query builder

## Hooks

To make it easy to always apply the same operation to every query ran, the repository has various hooks you can use to modify queries being ran, preparing entities before they're inserted into the database and more. Define these methods on your repository class and they will be invoked automatically.

- beforeQuery($query, boolean $many)
- afterQuery($results)
- beforeCreate($model, array $attributes)
- afterCreate($model, array $attributes)
- beforeUpdate($model, array $attributes)
- afterUpdate($model, array $attributes)

## Validation

While it can be discussed whether validation in a repository is appropriate, often it is very handy, especially in smaller applications.

For each action done by the repository ("create" and "update" out of the box), the method `valid($action, array $attributes)` is called on the validator object. This is made to work with my [Laravel Validation](https://github.com/anlutro/laravel-validation) package out of the box, but you can implement an interface and make it work with your own custom validator.

## Examples

Make sure the repository only ever returns rows related to a specific user.

	public function setUser($user)
	{
		$this->user = $user;
	}

	protected function beforeQuery($query, $many)
	{
		if (isset($this->user)) {
			$query->where('user_id', '=', $this->user->id);
		}
	}

Add a custom method that fetches all rows related to a specific user.

	public function getForUser($user)
	{
		$query = $this->newQuery();
		$query->where('user_id', '=', $user->id);
		return $this->fetchMany($query);
	}

Next: [Database repository](2-database.md)