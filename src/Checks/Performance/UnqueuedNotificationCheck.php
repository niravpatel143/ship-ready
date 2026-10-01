<?php

namespace ShipReady\Checks\Performance;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF016',
    title: 'Notification not queued',
    category: 'performance',
    severity: 'low'
)]
final class UnqueuedNotificationCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $notificationPath = app_path('Notifications');

        if (!is_dir($notificationPath)) {
            return;
        }

        foreach (glob($notificationPath . '/*.php') ?: [] as $file) {
            $ast = $context->code()->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder  = new NodeFinder();
            $classes = $finder->findInstanceOf($ast, Node\Stmt\Class_::class);

            foreach ($classes as $class) {
                if (!$this->extendsNotification($class)) {
                    continue;
                }

                if (!$this->implementsShouldQueue($class)) {
                    yield $this->finding(
                        message: "Notification '{$class->name}' does not implement ShouldQueue. It will be sent synchronously, blocking the HTTP request.",
                        file:    $file,
                        line:    $class->getStartLine(),
                        fix:     "Add `implements ShouldQueue` and `use Queueable;` to {$class->name} to dispatch asynchronously."
                    );
                }
            }
        }
    }

    private function extendsNotification(Node\Stmt\Class_ $class): bool
    {
        if ($class->extends === null) {
            return false;
        }

        $parent = $class->extends->toString();

        return $parent === 'Notification' || str_ends_with($parent, '\\Notification');
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
