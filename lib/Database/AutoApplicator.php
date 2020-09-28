<?php

namespace Instasell\Instarecord\Database;

use Instasell\Instarecord\Model;

/**
 * Applies values to auto-managed columns.
 */
class AutoApplicator
{
    const REASON_UPDATE = "update";
    const REASON_CREATE = "create";

    /**
     * @param Model $model The model for reading / writing the auto column to.
     * @param Column $col The column info.
     * @param string $reason The application reason (e.g. create or update).
     * @return bool Indicates whether the column value was set or changed.
     */
    public static function apply(Model $model, Column $col, string $reason): bool
    {
        if (!$col->hasAuto()) {
            return false;
        }

        $autoMode = $col->getAutoMode();
        $propName = $col->getPropertyName();

        // "Created" timestamp - apply if field is empty
        if ($autoMode === Column::AUTO_MODE_CREATED) {
            if (empty($model->$propName)) {
                $model->$propName = new \DateTime('now');
                return true;
            }

            return false;
        }

        // "Modified" timestamp - apply on every single change, for any reason
        // NB: the model itself is responsible for ensuring it is in "dirty" state before the applicator ever gets hit.
        if ($autoMode === Column::AUTO_MODE_MODIFIED) {
            $model->$propName = new \DateTime('now');
            return true;
        }

        return false;
    }
}