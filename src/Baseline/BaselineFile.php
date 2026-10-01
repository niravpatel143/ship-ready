<?php

namespace ShipReady\Baseline;

use ShipReady\Support\Finding;

final class BaselineFile
{
    public static function read(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public static function write(string $path, array $findings): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [];

        foreach ($findings as $finding) {
            if ($finding instanceof Finding) {
                $data[] = [
                    'fingerprint' => $finding->fingerprint(),
                    'checkId'     => $finding->checkId,
                    'message'     => $finding->message,
                    'severity'    => $finding->severity->value(),
                    'file'        => $finding->file,
                    'line'        => $finding->line,
                ];
            }
        }

        file_put_contents(
            $path,
            json_encode(['findings' => $data, 'generated_at' => date('c')], JSON_PRETTY_PRINT)
        );
    }
}
