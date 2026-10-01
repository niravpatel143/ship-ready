<?php

namespace ShipReady\Checks\Performance;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF008',
    title: 'Possible N+1 query: relation accessed in loop without eager loading',
    category: 'performance',
    severity: 'high'
)]
final class NPlusOneCheck extends AbstractCheck
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
                return $node instanceof Node\Stmt\Foreach_;
            });

            foreach ($loops as $loop) {
                /** @var Node\Stmt\Foreach_ $loop */
                $valueVar = $loop->valueVar;

                if (!($valueVar instanceof Node\Expr\Variable)) {
                    continue;
                }

                $varName = $valueVar->name;

                // Look for property accesses on the loop variable that look like relations
                $propFetches = $finder->findInstanceOf([$loop], Node\Expr\PropertyFetch::class);

                foreach ($propFetches as $fetch) {
                    if (!($fetch->var instanceof Node\Expr\Variable)) {
                        continue;
                    }

                    if ($fetch->var->name !== $varName) {
                        continue;
                    }

                    if (!($fetch->name instanceof Node\Identifier)) {
                        continue;
                    }

                    $propName = $fetch->name->name;

                    // Heuristic: lowercase plural property names are likely relations
                    if (strtolower($propName) === $propName && str_ends_with($propName, 's') && strlen($propName) > 3) {
                        // Check if this is a method call that chains further (indicating relation lazy load)
                        $parent = null; // We can't easily walk up the tree without a visitor

                        yield $this->finding(
                            message: "Possible N+1 query: accessing \${$varName}->{$propName} inside a foreach loop. This may trigger a query per iteration.",
                            file:    $file,
                            line:    $fetch->getLine(),
                            fix:     "Use eager loading: add ->with('{$propName}') to the query that produces the collection."
                        );

                        break; // One warning per loop
                    }
                }
            }
        }
    }
}
