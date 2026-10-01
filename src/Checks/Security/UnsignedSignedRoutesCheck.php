<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC025',
    title: 'signedRoute() used without signed middleware',
    category: 'security',
    severity: 'medium'
)]
final class UnsignedSignedRoutesCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Find routes that use signedRoute in generated URLs but lack signed middleware
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                // Find URL::signedRoute or route() with signed option
                if (preg_match('/URL::(?:signed|temporarySigned)Route|signedRoute\s*\(/', $line)) {
                    // Now check if the target route has signed middleware
                    // This is a heuristic — we look for the route name being used
                    if (preg_match('/["\']([a-zA-Z0-9._-]+)["\']/', $line, $matches)) {
                        $routeName = $matches[1];

                        $routes = $context->routes()->withName([$routeName]);

                        foreach ($routes as $route) {
                            if (!$route->hasMiddleware('signed') && !$route->hasMiddlewareStartingWith('signed')) {
                                yield $this->finding(
                                    message: "signedRoute('{$routeName}') is generated but the route has no 'signed' middleware to verify the signature.",
                                    file:    $file,
                                    line:    $lineNo + 1,
                                    fix:     "Add ->middleware('signed') to the '{$routeName}' route definition."
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
