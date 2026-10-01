<?php

namespace ShipReady\Compat;

use ShipReady\Checks\CheckMeta;

final class VersionGuard
{
    public static function passes(CheckMeta $meta, string $laravelVersion): bool
    {
        if ($meta->minLaravel && version_compare($laravelVersion, $meta->minLaravel, '<')) {
            return false;
        }

        if ($meta->maxLaravel && version_compare($laravelVersion, $meta->maxLaravel, '>')) {
            return false;
        }

        return true;
    }
}
