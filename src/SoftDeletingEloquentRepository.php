<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

use Illuminate\Database\Eloquent\Model;

class SoftDeletingEloquentRepository extends EloquentRepository
{

    /**
     * @var boolean
     */
    protected $onlyTrashed = false;

    /**
     * @var boolean
     */
    protected $withTrashed = false;

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function restore($model)
    {
        $model->restore();
    }

    /**
     * Limit to trashed entities.
     */
    public function onlyTrashed()
    {
        $this->onlyTrashed = true;
        $this->withTrashed = false;
    }

    /**
     * Return trashed entites aswell.
     */
    public function withTrashed()
    {
        $this->onlyTrashed = false;
        $this->withTrashed = true;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newQuery()
    {
        $query = parent::newQuery();

        if ($this->onlyTrashed === true) {
            $query->onlyTrashed();
        }

        if ($this->withTrashed === true) {
            $query->withTrashed();
        }

        return $query;
    }

}
