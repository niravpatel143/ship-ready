<?php

namespace ShipReady\Suppression;

final class InlineIgnoreParser
{
    /**
     * Parse a PHP file for @ship-ignore inline comments.
     * Format: // @ship-ignore CHECKID optional reason text
     */
    public function parse(string $file): SuppressionCollection
    {
        $collection = new SuppressionCollection();

        if (!file_exists($file) || !is_readable($file)) {
            return $collection;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return $collection;
        }

        $pattern = '/\/\/\s*@ship-ignore\s+([A-Z0-9_]+)(?:\s+(.+))?$/';

        foreach ($lines as $line) {
            if (preg_match($pattern, trim($line), $matches)) {
                $checkId = $matches[1];
                $reason  = isset($matches[2]) ? trim($matches[2]) : null;

                $collection->add($checkId, $reason);
            }
        }

        return $collection;
    }
}
