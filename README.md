# Laravel 4 Repository classes
Installation: `composer require anlutro/l4-repository`

Pick the latest stable version from packagist or the GitHub tag list.

This package utilizes and depends on my [validation service library](https://github.com/anlutro/laravel-validation).

WARNING: Backwards compatibility is not guaranteed during version 0.x.

### Usage
A repository is a class that lies between the controller and the model to make the controller more lightweight and allows you to more easily re-use database logic in your application.

The repository comes with some standard methods already, like getByKey and getAll. You may add as many custom methods you want to the repository. Overwrite the constructor method to inject your own model and validator. The public methods available by default are:

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

- prepareQuery($query, $many) - is ran before every select query. $many is a boolean indicating whether one or many rows are being fetched.
- prepareCreate($model, $input) - is ran before every create action after instantiation and mass assignment has been done.
- prepareUpdate($model, $input) - is ran before every update action after mass assignment has been done.
- prepareModel($model) - after a single model is retrieved from the database
- prepareCollection($collection) - after a collection is retrieved
- preparePaginator($paginator) - after a paginated result is retrieved

The repository also has methods you can hook into to prevent saves from happening. The following methods should return true or false:

- readyForSave($model) - ran before every create/update to check if a model is in a state to be saved to the database.
- readyForCreate($model) - as above, but only for creates.
- readyForUpdate($model) - as above, but only for updates.
- canBeUpdated($model) - this is called before the model is updated/filled with input data, use it to determine if a model should even be allowed to be updated.

Add errors to the repository by doing `$this->errors->add($key, $message)`. Make sure to add an error before returning false.

### Eloquent repository
For eloquent repositories you need to extend the class and override the constructor. Type hint towards your own model and validator class to inject them automatically and call `parent::__construct($model, $validator);` (the validator is optional).

### Database repository
For database repositories you need to set `protected $table = 'mytable'` and optionally `protected $primaryKey = 'some_id'`. If you want to inject a validator you can either call `setValidator` or override the constructor, but remember to inject `Illuminate\Database\Connection` and call `parent::__construct($connection)`.

### Validation
If a validator is set on the repository, validation is done automatically. If methods like update() and create() return false, validation errors are available via the getErrors() method. The "readyFor" and "canBe" methods can be used to do extra validation inside the repository if necessary. You can add an error to the existing ones by calling `$this->errors->add('key', 'Some error message')`.

## Contact
Open an issue on GitHub if you have any problems or suggestions.

## License
The contents of this repository is released under the [MIT license](http://opensource.org/licenses/MIT).
