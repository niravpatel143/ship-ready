<?php

namespace ShipReady\Support;

final class Finding
{
    public string $checkId;
    public string $message;
    public Severity $severity;
    public ?string $file;
    public ?int $line;
    public ?string $fix;
    public array $context;

    public function __construct(
        string $checkId,
        string $message,
        Severity $severity,
        ?string $file = null,
        ?int $line = null,
        ?string $fix = null,
        array $context = []
    ) {
        $this->checkId  = $checkId;
        $this->message  = $message;
        $this->severity = $severity;
        $this->file     = $file;
        $this->line     = $line;
        $this->fix      = $fix;
        $this->context  = $context;
    }

    public function fingerprint(): string
    {
        return hash('sha256', implode('|', [
            $this->checkId,
            $this->file ?? '',
            $this->message,
        ]));
    }

    public static function internalError(string $checkId, \Throwable $e): self
    {
        return new self(
            checkId: $checkId,
            message: 'Internal error running check: ' . $e->getMessage(),
            severity: Severity::high(),
            file: $e->getFile(),
            line: $e->getLine(),
            fix: 'Report this as a bug in the ShipReady package.',
            context: [
                'exception' => get_class($e),
                'trace'     => $e->getTraceAsString(),
            ]
        );
    }
}
