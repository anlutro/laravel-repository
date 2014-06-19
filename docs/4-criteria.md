# Criteria

Criteria are classes that know something about the criteria a query should fulfill, and then how to apply those criteria onto a query builder object.

Criteria are a good way to prevent 1000+ line long repository classes as it lets you spread your conditional logic out more.

A very simple example to replace the example in chapter 1:

	class MatchesUserCriteria implements CriteriaInterface
	{
		public function __construct($user)
		{
			$this->user = $user;
		}

		public function apply($query)
		{
			$query->where('user_id', '=' $this->user->id);
		}
	}

To apply the criteria to the query:

	$criteria = new MatchesUserCriteria($user);

	// get all rows matching user
	$repository->getByCriteria($criteria);

	// apply more than one criteria
	$repository->pushCriteria($criteria1);
	$repository->pushCriteria($criteria2);
	$repository->getAll();

Of course, it is entirely up to you whether instantiation/pushing of criteria is done inside the repository or outside.

Criteria are flushed after each query, and are as such not meant for static/persistant query operations, but rather more complex single operations.

## Default criteria

On your repository you can define an array of class names that are criteria that are always applied to every query.

	protected $defaultCriteria = [
		'MyNamespace\CriteriaOne',
		'OtherNamespace\CriteriaTwo',
	];

This is rarely useful as you can't call any methods on these criteria and they can't be resolved from the IoC container. Instead, you could inject the default criteria via the contructor or instantiate them manually and add them via the protected `addDefaultCriteria($criteria)` method.

Previous: [Eloquent repository](3-eloquent.md)
