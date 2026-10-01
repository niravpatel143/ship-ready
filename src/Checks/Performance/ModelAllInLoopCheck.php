<?php

namespace ShipReady\Checks\Performance;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF007',
    title: 'Model::all() called inside a loop',
    category: 'performance',
    severity: 'high'
)]
final class ModelAllInLoopCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $ast = $context->code()->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder = new NodeFinder();
            $loops  = $finder->find($ast, function (Node $node) {
                return $node instanceof Node\Stmt\For_
                    || $node instanceof Node\Stmt\Foreach_
                    || $node instanceof Node\Stmt\While_
                    || $node instanceof Node\Stmt\Do_;
            });

            foreach ($loops as $loop) {
                $calls = $finder->findInstanceOf([$loop], Node\Expr\StaticCall::class);

                foreach ($calls as $call) {
                    if (!($call->name instanceof Node\Identifier)) {
                        continue;
                    }

                    if ($call->name->name !== 'all') {
                        continue;
                    }

                    yield $this->finding(
                        message: 'Model::all() called inside a loop. This issues a full-table SELECT on every iteration.',
                        file:    $file,
                        line:    $call->getLine(),
                        fix:     'Move Model::all() outside the loop, or use a more targeted query with where() conditions.'
                    );
                }
            }
        }
    }
}
