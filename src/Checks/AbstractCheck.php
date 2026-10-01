<?php

namespace ShipReady\Checks;

use ShipReady\Support\Finding;
use ShipReady\Support\Severity;

abstract class AbstractCheck implements Check
{
    protected function finding(
        string $message,
        ?string $file = null,
        ?int $line = null,
        ?string $fix = null,
        array $context = []
    ): Finding {
        $meta = CheckMetaReader::for(static::class);

        return new Finding(
            checkId:  $meta->id,
            message:  $message,
            severity: Severity::fromString($meta->severity),
            file:     $file,
            line:     $line,
            fix:      $fix,
            context:  $context
        );
    }

    protected function findingWithSeverity(
        string $message,
        Severity $severity,
        ?string $file = null,
        ?int $line = null,
        ?string $fix = null,
        array $context = []
    ): Finding {
        $meta = CheckMetaReader::for(static::class);

        return new Finding(
            checkId:  $meta->id,
            message:  $message,
            severity: $severity,
            file:     $file,
            line:     $line,
            fix:      $fix,
            context:  $context
        );
    }
}
