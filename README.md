# Laravel 4 Repositories [![Build Status](https://travis-ci.org/anlutro/laravel-repository.png?branch=master)](https://travis-ci.org/anlutro/laravel-repository) [![Latest Version](http://img.shields.io/github/tag/anlutro/laravel-repository.svg)](https://github.com/anlutro/laravel-repository/releases)
Installation: `composer require anlutro/l4-repository`

Pick the latest stable version from packagist or the GitHub tag list. If you use dev-master you WILL get breaking and experimental changes.

This package utilizes and depends on my [validation service library](https://github.com/anlutro/laravel-validation).

WARNING: Backwards compatibility is not guaranteed during version 0.x.

### Usage
A repository is a class that lies somewhere between the controller and the database logic to make your application's service layers (including controllers) more lightweight and allows you to more easily re-use database logic in your application.

The repository comes with some standard methods already, like getByKey and getAll. You may add as many custom methods you want to the repository. The public methods available by default are:

- getAll()
- getByKey($key)
- getNew($attributes) - no validation, simply a new entity
- create($attributes) - validate and attempt to create a new entity
- update($entity, $attributes) - validate and attempt to update an existing entity
- delete($entity) - attempt to delete an entity

You can toggle pagination on and off by using paginate(false) or paginate(20). You can toggle exceptions with the toggleExceptions methd - this will throw an exception instead of returning null when a specific row wasn't found. This is useful for an API or where you otherwise have a generic error handler for the exception. These methods can be chained, so you can for example do paginate(20)->getAll().

The repository has various "hooks" to perform additional validation, safety checks or to apply certain restrictions on every query.

- beforeQuery($query, $many)
- afterQuery($results)
- beforeCreate($model, $attributes)
- afterCreate($model, $attributes)
- beforeUpdate($model, $attributes)
- afterUpdate($model, $attributes)

If any of the "before" actions return false, the action itself will not be executed. If you do return false from a "before" action, you should also make sure to add errors to the repository by doing `$this->errors->add($key, $message)`, so that the user of the repository knows what the problem was.

### Eloquent repository
For eloquent repositories you need to extend the class and override the constructor. Type hint towards your own model and validator class to inject them automatically and call `parent::__construct($model, $validator);` (the validator is optional).

Eloquent repositories have the additional action layer of `save`, which means you can hook into the `beforeSave` and `afterSave` actions as well as create/update.

### Database repository
For database repositories you need to set `protected $table = 'mytable'` and optionally `protected $primaryKey = 'some_id'`. If you want to inject a validator you can either call `setValidator` or override the constructor, but remember to inject `Illuminate\Database\Connection` and call `parent::__construct($connection, $validator)`.

### Validation
If a validator is set on the repository, validation is done automatically. If methods like update() and create() return false, validation errors are available via the getErrors() method. The "before" actions can be used to do extra validation inside the repository if necessary. You can always add an error to the existing ones by calling `$this->errors->add('key', 'Some error message')`.

### Extending
The repository is made for extending and adding your own custom behaviour. For example, the default create/update actions in the Eloquent repository simply call `fill($attributes)->save()` on the model instance, but because not every attribute is made fillable for security reasons, you may want to sometimes add non-fillable attributes to the model. Here's how you'd do it.

First of all, create a new public method that's named sensibly - for example, if you have a user system, you want `register()` to behave differently from `create`.

```php
public function register(array $attributes)
{
    $model = $this->getNew($attributes); // fills fillable attributes
    $model->is_active = false; // non-fillable attribute
    $model->pending = true;
    return $this->perform('create', $model, $attributes);
}
```

This calls the default validCreate on the validator as well as beforeCreate and afterCreate. If you want to add your own custom before/after handlers (i.e. beforeRegister and afterRegister) you do the following:

```php
public function register(array $attributes)
{
    $model = $this->getNew($attributes); // fills fillable attributes
    return $this->perform('register', $model, $attributes);
}

public function performRegister(array $attributes)
{
	$model->is_active = false; // non-fillable attribute
	$model->pending = true;
	// false in the following means "don't validate"
	return $this->perform('save', $model, $attributes, false);
}
```

This has two side-effects: First of all, validRegister is called on our validator. Second, beforeRegister and afterRegister are called respectively. If you want, you can wrap actions inside actions - you could call `perform('create')` instead of `perform('save')` inside `performRegister`, for example. This way you can have a multi-layer validation/preparation setup if you so wish.

## Changelog

### 0.5
Major refactor: Replaced readyFor*, canBe*, prepare* methods with before* and after*

See the examples directory and the integration tests for examples of the new structure.

### 0.4
Moved logic to an abstract class to allow more work on the DatabaseRepository.

- Boolean flag for calling push() instead of save() in EloquentRepository

### 0.3
Update primarily to work with validator 0.3 and up.

- Preserve array keys on errors/getErrors (0.3.1)
- Fixed a typo causing an error (0.3.2)
- Optional action argument for makeNew to allow different types of create validation (0.3.3)
- Reset errors between each create/update (0.3.3)

### 0.2
Update primarily to work with validator 0.2 and up.

### 0.1
Initial release.

## Contact
Open an issue on GitHub if you have any problems or suggestions.

## License
The contents of this repository is released under the [MIT license](http://opensource.org/licenses/MIT).
