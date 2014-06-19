# Database repository

To create a database repository, we simply need to define the table that should be queried by default.

	use anlutro\LaravelRepository\DatabaseRepository;
	
	class MyRepository extends DatabaseRepository
	{
		protected $table = 'my_table';
	}

Previous: [Repositories](1-reposity.md)
Next: [Eloquent repository](3-eloquent.md)
