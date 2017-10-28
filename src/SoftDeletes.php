<?php

namespace Holloway;

use Carbon\Carbon;

trait SoftDeletes
{
    /**
     * Indicates if the mapper is going to be performing soft deletes.
     *
     * @var bool
     */
    protected $isSoftDeleting = true;

    /**
     * Restore a soft-deleted mapper instance.
     *
     * @param  mixed $entity
     * @return bool|null
     */
    public function restore($entity)
    {
        if (is_iterable($entity)) {
            return $this->restoreEntities($entity, $force);
        } else {
            return $this->restoreEntity($entity, $force);
        }
    }

    /**
     * @param  mixed $entity
     * @return bool|null
     */
    public function restoreEntity($entity) : ?bool
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if (static::$eventManager->fire('restoring', $entity) === false) {
            return false;
        }

        $this->getConnection()
            ->table($this->getTableName())
            ->where($this->getPrimaryKeyName(), $this->getIdentifier($entity))
            ->update([$this->getQualifiedDeletedAtColumn(), null]);

        static::$eventManager->fire('removed', $entity);

        return $result;
    }

    /**
     * @param  iterable $entities
     * @return bool|null
     */
    public function restoreEntities(iterable $entities) : ?bool
    {
        $this->getConnection()->transaction(function() use ($entities) {
            foreach($entities as $entity) {
                $this->restoreEntity($entity);
            }
        });

        return true;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumnName()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->getTableName().'.'.$this->getDeletedAtColumnName();
    }
}