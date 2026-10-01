<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC005',
    title: 'Model with no fillable or guarded property',
    category: 'security',
    severity: 'high'
)]
final class MassAssignmentCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $classes = $context->code()->classesExtending('Illuminate\Database\Eloquent\Model');

        foreach ($classes as $class) {
            $fillable = $class->property('fillable');
            $guarded  = $class->property('guarded');

            // If neither property exists, the model has no mass assignment protection
            if ($fillable === null && $guarded === null) {
                yield $this->finding(
                    message: "Model {$class->name} has no \$fillable or \$guarded property defined.",
                    file:    $class->file,
                    line:    $class->line,
                    fix:     "Add a \$fillable array to whitelist assignable columns, or set \$guarded = [] and add all column names to \$fillable."
                );

                continue;
            }

            // Warn if $guarded is an empty array (fully unguarded)
            if ($guarded !== null && $guarded->isEmptyArray()) {
                yield $this->finding(
                    message: "Model {$class->name} has \$guarded = [] which allows all columns to be mass-assigned.",
                    file:    $class->file,
                    line:    $guarded->line,
                    fix:     "Explicitly list columns in \$fillable instead of using \$guarded = []."
                );
            }
        }
    }
}
