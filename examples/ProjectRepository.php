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
        if (!Auth::check()) {
            $query->where('access_level', '<=', 1);
            return;
        }

        $user = Auth::user();

        // unless user is an admin, we restrict every query to only show
        // projects with access_level lower than the logged in user's.
        if (!$user->isAdmin()) {
            $query->where('access_level', '<=', $user->access_level);
        }
    }

    /**
     * This method is called after fetchMany when not paginating. We use it to
     * perform operations on a collection of models before it is returned
     * from the repository.
     *
     * Changed from prepareCollection/preparePaginator in 0.5
     */
    protected function afterQuery($projects)
    {
        // let's say we want to add some data onto the models after they've
        // been retrieved from the database. we want "ExtraData" which is a
        // month or less old.
        $dt = Carbon\Carbon::now()->subMonth();
        $extraData = ExtraData::where('created_at', '>', $dt)->get();

        // iterate through the collection of projects...
        foreach ($projects as $project) {
            // filter out the extra data related to the project using our own logic
            $project->extra_data = $extraData->filter(function($extra) use($project) {
                return $extra->is_project && $extra->user_id == $project->owner_id;
            });
        }
    }

    /**
     * Determines if a model can be stored to the database or not. Use it for
     * stuff that can't (easily) be done by the validator.
     *
     * Changed from readyForCreate in 0.5
     */
    protected function beforeCreate($project)
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
}
