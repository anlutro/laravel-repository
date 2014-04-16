<?php
/**
 * An example repository for projects.
 * 
 * $projectRepo->onlyActive()->getAll();
 * $projectRepo->onlyInactive()->getAll();
 * $projectRepo->onlyInactive()->paginate(20)->getAll();
 * $projectRepo->toggleExceptions(true)->getByKey($id);
 */
class ProjectRepository extends \anlutro\LaravelRepository\EloquentRepository
{
    /**
     * Override the constructor. In PHP, you can override type hints in constructors,
     * but not in normal methods. Weird behaviour but we can use this to our
     * advantage.
     */
    public function __construct(ProjectModel $project, ProjectValidator $validator)
    {
        parent::__construct($project, $validator);
    }

    /**
     * Whether to get all projects regardless of status (null), only active
     * projects (true) or only inactive projects (false).
     */
    protected $active = null;

    /**
     * Get only active projects.
     *
     * @return static
     */
    public function onlyActive()
    {
        $this->active = true;
        return $this;
    }

    /**
     * Get only inactive projects.
     *
     * @return static
     */
    public function onlyInactive()
    {
        $this->active = false;
        return $this;
    }

    /**
     * You can add as many setter/getter methods to your repositories as you
     * wish if you need extra behaviour. For this example, let's assume the
     * repository needs access to the currently logged in user. Instead of using
     * Auth::user() in the repository, we add a setter method which we can call
     * from the controller - $repo->setCurrentUser(Auth::user());
     */
    public function setCurrentUser(MyUserModel $user)
    {
        $this->user = $user;
    }

    /**
     * Determines if a model can be stored to the database or not. Use it for
     * stuff that can't (easily) be done by the validator.
     *
     * Changed from readyForCreate in 0.5
     */
    protected function beforeCreate($project, $attributes)
    {
        // each project has an owner (a user). let's make sure that the project
        // owner is an active user - if not, add an error and return false. we
        // assume that project->owner is always populated.
        if (!$project->owner->is_active) {
            $this->errors->add('owner', 'Project owner must be an active user.');
            return false;
        }

        return true;
    }

    /**
     * Using afterSave, afterCreate or afterUpdate to set relationships is
     * often very useful. afterSave is called on both update and create.
     */
    public function afterSave($project, $attributes)
    {
        // if "relation" is not present in the input, we simply detach all
        // related models by passing an empty array.
        $ids = isset($attributes['relation']) ? $attributes['relation'] : [];
        $project->manyToManyRelation()->sync($ids);
    }

    public function afterCreate($project, $attributes)
    {
        if (!$project->user && $this->user) {
            $project->owner()->associate($this->user);
        }
    }

    /**
     * This method is called before fetchMany and fetchSingle. We use it to add
     * functionality that should be present on every query.
     *
     * Renamed from prepareQuery in 0.5
     */
    protected function beforeQuery($query, $many)
    {
        // if this->active has been set to something, we add it to the query
        if ($this->active !== null) {
            $query->where('is_active', '=', $this->active);
        }

        // we want to always eager load members and comments.
        $query->with('members', 'comments');

        // let's say guests can only view projects with access level 1 or less.
        if (!$this->user) {
            $query->where('access_level', '<=', 1);
        }

        // unless user is an admin, we restrict every query to only show
        // projects with access_level lower than the logged in user's.
        else if (!$this->user->isAdmin()) {
            $query->where('access_level', '<=', $user->access_level);
        }
    }

    /**
     * This method is called after fetchMany and fetchSingle. It is most useful
     * to append attributes onto more than one model for stuff that can't easily
     * be done via the query builder. Sometimes, for example, a subselect or
     * join or groupby can mess with the paginator.
     *
     * Changed from prepareCollection/preparePaginator in 0.5
     */
    protected function afterQuery($result)
    {
        // $result can be a single model, a paginator or a collection. We can
        // standardize the variable in this way to be able to use the same code
        // on all three types.
        if ($result instanceof \Illuminate\Database\Eloquent\Model) {
            // we might just do `return;` here if we didn't want our logic
            // applied to a single model.
            $projects = $result->newCollection([$result]);
        } else if ($result instanceof \Illuminate\Pagination\Paginator) {
            $projects = $result->getCollection();
        }

        // let's say we want to add some data onto the models after they've
        // been retrieved from the database. we want to add the count of a
        // relation, but don't want to mess with sub-selects and don't want to
        // waste memory by eager loading the relation, nor do we want to run
        // one query for each model.

        // build the query that gets the relationship count. some of this logic
        // could be moved into model scopes if you want. this is a rather
        // complex query so if you don't understand what is going on here,
        // don't worry too much. basically just getting the number of relations
        // attached to each project.
        $results = $this->model->relation()
            ->getRelated()->newQuery()
            ->whereIn('parent_id', $projects->lists('id'))
            ->selectRaw('parent_id, count(*) as relation_count')
            ->groupBy('parent_id')
            ->lists('relation_count', 'parent_id');

        // now add the attribute to each model.
        $projects->each(function($project) {
            $project->relation_count = $results[$project->id];
        });
    }
}
