<?php

namespace ShipReady\Checks\Security;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC031',
    title: 'Model exposes sensitive attributes in JSON output',
    category: 'security',
    severity: 'high'
)]
final class ModelMissingHiddenCheck extends AbstractCheck
{
    private const SENSITIVE_FIELDS = [
        'password', 'password_hash', 'remember_token', 'api_token', 'api_key',
        'secret', 'private_key', 'secret_key', 'two_factor_secret',
        'two_factor_recovery_codes', 'stripe_id',
    ];

    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $ast = $context->code()->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder  = new NodeFinder();
            $classes = $finder->findInstanceOf($ast, Node\Stmt\Class_::class);

            foreach ($classes as $class) {
                if (!$this->extendsModel($class)) {
                    continue;
                }

                $fillable = $this->getArrayStringValues($class, 'fillable');
                $casts    = $this->getArrayKeys($class, 'casts');
                $hidden   = $this->getArrayStringValues($class, 'hidden');

                $exposed = array_unique(array_merge($fillable, $casts));

                foreach ($exposed as $field) {
                    $fieldLower = strtolower($field);

                    foreach (self::SENSITIVE_FIELDS as $sensitive) {
                        if ($fieldLower === $sensitive || str_ends_with($fieldLower, '_' . $sensitive)) {
                            if (!in_array($field, $hidden, true)) {
                                yield $this->finding(
                                    message: "Model '{$class->name}' has sensitive field '{$field}' not listed in \$hidden — it will appear in toArray() / toJson() output.",
                                    file:    $file,
                                    line:    $class->getStartLine(),
                                    fix:     "Add '{$field}' to the \$hidden array in {$class->name} to prevent it from being serialised."
                                );
                            }
                            break;
                        }
                    }
                }
            }
        }
    }

    private function extendsModel(Node\Stmt\Class_ $class): bool
    {
        if ($class->extends === null) {
            return false;
        }

        $parent = $class->extends->toString();

        return in_array($parent, ['Model', 'Authenticatable', 'Pivot'], true)
            || str_ends_with($parent, '\\Model')
            || str_ends_with($parent, '\\Authenticatable');
    }

    private function getArrayStringValues(Node\Stmt\Class_ $class, string $property): array
    {
        $values = [];

        foreach ($class->stmts as $stmt) {
            if (!($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                if ($prop->name->name !== $property || !($prop->default instanceof Node\Expr\Array_)) {
                    continue;
                }

                foreach ($prop->default->items as $item) {
                    if ($item !== null && $item->value instanceof Node\Scalar\String_) {
                        $values[] = $item->value->value;
                    }
                }
            }
        }

        return $values;
    }

    private function getArrayKeys(Node\Stmt\Class_ $class, string $property): array
    {
        $values = [];

        foreach ($class->stmts as $stmt) {
            if (!($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                if ($prop->name->name !== $property || !($prop->default instanceof Node\Expr\Array_)) {
                    continue;
                }

                foreach ($prop->default->items as $item) {
                    if ($item !== null && $item->key instanceof Node\Scalar\String_) {
                        $values[] = $item->key->value;
                    }
                }
            }
        }

        return $values;
    }
}
