<?php

namespace ShipReady\Analyzers;

final class CallInfo
{
    public string $name;
    public string $file;
    public int $line;
    public array $args;

    public function __construct(
        string $name,
        string $file,
        int $line,
        array $args = []
    ) {
        $this->name = $name;
        $this->file = $file;
        $this->line = $line;
        $this->args = $args;
    }

    public function hasVariableArg(): bool
    {
        foreach ($this->args as $arg) {
            if (is_array($arg) && isset($arg['type']) && $arg['type'] === 'variable') {
                return true;
            }

            if (is_string($arg) && strncmp($arg, '$', 1) === 0) {
                return true;
            }
        }

        return false;
    }

    public function firstArgIsVariable(): bool
    {
        if (empty($this->args)) {
            return false;
        }

        $first = $this->args[0];

        if (is_array($first) && isset($first['type'])) {
            return in_array($first['type'], ['variable', 'concat', 'interpolation'], true);
        }

        return false;
    }
}
