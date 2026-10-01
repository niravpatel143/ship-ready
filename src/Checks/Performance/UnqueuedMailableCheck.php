<?php

namespace ShipReady\Checks\Performance;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF017',
    title: 'Mailable not queued',
    category: 'performance',
    severity: 'low'
)]
final class UnqueuedMailableCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $mailPath = app_path('Mail');

        if (!is_dir($mailPath)) {
            return;
        }

        foreach (glob($mailPath . '/*.php') ?: [] as $file) {
            $ast = $context->code()->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder  = new NodeFinder();
            $classes = $finder->findInstanceOf($ast, Node\Stmt\Class_::class);

            foreach ($classes as $class) {
                if (!$this->extendsMailable($class)) {
                    continue;
                }

                if (!$this->implementsShouldQueue($class)) {
                    yield $this->finding(
                        message: "Mailable '{$class->name}' does not implement ShouldQueue. Sending this email blocks the current request.",
                        file:    $file,
                        line:    $class->getStartLine(),
                        fix:     "Add `implements ShouldQueue` to {$class->name} to dispatch emails asynchronously via the queue."
                    );
                }
            }
        }
    }

    private function extendsMailable(Node\Stmt\Class_ $class): bool
    {
        if ($class->extends === null) {
            return false;
        }

        $parent = $class->extends->toString();

        return $parent === 'Mailable' || str_ends_with($parent, '\\Mailable');
    }

    private function implementsShouldQueue(Node\Stmt\Class_ $class): bool
    {
        foreach ($class->implements as $iface) {
            $name = $iface->toString();

            if ($name === 'ShouldQueue' || str_ends_with($name, '\\ShouldQueue')) {
                return true;
            }
        }

        return false;
    }
}
