<?php

namespace ShipReady\Checks\Octane;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'OCT005',
    title: 'Service provider stores $app or $container in static property',
    category: 'octane',
    severity: 'high'
)]
final class ContainerStateLeakCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $octaneConfig = config_path('octane.php');

        if (!file_exists($octaneConfig)) {
            return;
        }

        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            if (!preg_match('/extends\s+ServiceProvider\b/', $content)) {
                continue;
            }

            // Detect static::$app or self::$app = ... in service providers
            if (preg_match('/(?:static|self)::\$(?:app|container)\s*=/', $content)) {
                yield $this->finding(
                    message: 'Service provider stores $app/$container in a static property. Under Octane the container is shared across requests — this causes cross-request contamination.',
                    file:    $file,
                    fix:     'Use instance properties only, or use app() helper inside methods to resolve fresh instances per request.'
                );
            }
        }
    }
}
