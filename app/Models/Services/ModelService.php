<?php

namespace App\Models\Services;

use Illuminate\Database\Eloquent\Model;

class ModelService
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return $this
     */
    public function fresh(): self
    {
        $this->model = $this->getModel()->fresh();

        return $this;
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function patch(array $attributes)
    {
        return $this->getModel()->update($attributes);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function delete(): bool
    {
        return $this->getModel()->delete();
    }

    /**
     * @return bool|null
     */
    public function forceDelete()
    {
        return $this->getModel()->forceDelete();
    }
}
