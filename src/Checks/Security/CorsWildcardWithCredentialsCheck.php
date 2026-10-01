<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC015',
    title: 'CORS wildcard origin combined with credentials support',
    category: 'security',
    severity: 'critical'
)]
final class CorsWildcardWithCredentialsCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $config = $context->config();

        $allowedOrigins    = $config->get('cors.allowed_origins', []);
        $supportsCredentials = $config->get('cors.supports_credentials', false);

        $hasWildcard = in_array('*', (array)$allowedOrigins, true);

        if ($hasWildcard && $supportsCredentials) {
            yield $this->finding(
                message: 'CORS is configured with allowed_origins = ["*"] and supports_credentials = true. Browsers will block this combination, but it indicates a misconfiguration that may allow credential theft.',
                fix:     'Either restrict cors.allowed_origins to specific domains, or set cors.supports_credentials to false for public APIs.'
            );
        }
    }
}
