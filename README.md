# Laravel 4 Repository classes
Installation: `composer require anlutro/l4-repository`

Pick the latest stable version from packagist or the GitHub tag list.

This package utilizes and depends on my [validation service library](https://github.com/anlutro/laravel-validation).

WARNING: Backwards compatibility is not guaranteed during version 0.x.

### Eloquent repository
Location: src/EloquentRepository.php

A repository is a class that lies between the controller and the model to make the controller more lightweight and allows you to more easily re-use database logic in your application.

Extend the class and override the constructor. Type hint towards your own model and validator class to inject them automatically and call `parent::__construct($model, $validator);`.

The repository comes with some standard methods already, like getByKey and getAll. You may add as many custom methods you want to the repository. Overwrite the constructor method to inject your own model and validator. The methods available by default are:

- getAll()
- getByKey($key)
- dryUpdate($model, $attributes) - validate and update, don't save
- update($model, $attributes) - validate, update and save
- delete($model)
- getNew($attributes) - no validation, simply a new model instance
- makeNew($attributes) - does create validation, doesn't save to database
- create($attributes) - does create validation, saves to database

You can toggle pagination on and off by using paginate(false) or paginate(20). You can toggle exceptions with the toggleExceptions methd - this will make sure that firstOrFail is called instead of first - useful for an API or where you otherwise have a generic error handler for the ModelNotFound exception. These methods can be chained, so you can for example do paginate(20)->getAll().

The repository has various "hooks" to perform additional validation, safety checks or to apply certain restrictions on every query.

- prepareQuery($query, $many) - is ran before every query. $many is a boolean indicating whether one or many rows are being fetched.
- prepareModel($model) - after a single model is retrieved from the database
- prepareCollection($collection) - after a collection is retrieved
- preparePaginator($paginator) - after a paginated result is retrieved

The repository also utilizes validation. If methods like update() and create() return false, validation errors are available via the errors() method. In addition, you can do extra validation in the repository where you have access to the actual models. The following hooks are available - all of them should return either true or false.

- readyForSave($model) - ran before every create/update to check if a model is in a state to be saved to the database.
- readyForCreate($model) - as above, but only for creates.
- readyForUpdate($model) - as above, but only for updates.
- canBeUpdated($model) - this is called before the model is updated/filled with input data, use it to determine if a model should even be allowed to be updated.

Add errors by doing `$this->errors->add($key, $message)`.

### Database repository
This repository is incomplete, but the idea is to just use the raw query builder instead of models.

## Contact
Open an issue on GitHub if you have any problems or suggestions.

## License
The contents of this repository is released under the [MIT license](http://opensource.org/licenses/MIT).
