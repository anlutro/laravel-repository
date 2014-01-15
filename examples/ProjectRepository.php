<?php
/**
 * An example repository for projects.
 * 
 * $projectRepo->onlyActive()->getAll();
 * $projectRepo->onlyInactive()->getAll();
 * $projectRepo->onlyInactive()->paginate(20)->getAll();
 * $projectRepo->toggleExceptions(true)->getByKey($id);
 */
class ProjectRepository extends \c\EloquentRepository
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
     */
    protected function prepareQuery($query, $many)
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
     */
    protected function prepareCollection($projects)
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
     */
    protected function readyForCreate($project)
    {
        // each project has an owner (a user). let's make sure that the project
        // owner is an active user - if not, add an error and return false. we
        // assume that project->owner is always populated.
        if (!$project->owner->is_active) {
            $this->errors->add('owner', 'Project owner must be an active user.');
            return false;
        }

        // remember to always return true if it is valid!
        return true;
    }

    /**
     * The project model itself has only a few fillable attributes to prevent
     * normal users from editing the form before submitting it and sending in
     * a bunch of illegal data. However, sometimes we want to allow more fields
     * to be updated - for example if a project manager is updating it. In this
     * case, we make an extra method.
     */
    public function updateAsManager($project, array $attributes)
    {
        // first we call dryUpdate which will do validation and pre-requesites.
        // the third string param will be used when validating, so the method
        // called on the validator will be 'validUpdateAsManager' instead of the
        // default 'validUpdate' - this also means you can add the method
        // 'getUpdateAsManagerRules' to your validator for custom validation
        // rules specifically for this context.
        if (!$this->dryUpdate($project, $attributes, 'updateAsManager')) {
            return false;
        }

        // update extra fields...
        $project->owner_id = $attributes['owner'];
        $project->deadline = $attributes['deadline'];

        // and return whether the save was successful
        return $project->save();
    }
}
