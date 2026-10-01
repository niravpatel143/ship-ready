<?php

namespace ShipReady\Analyzers;

final class EchoInfo
{
    public string $expression;
    public string $file;
    public int $line;

    public function __construct(string $expression, string $file, int $line)
    {
        $this->expression = $expression;
        $this->file       = $file;
        $this->line       = $line;
    }
}
