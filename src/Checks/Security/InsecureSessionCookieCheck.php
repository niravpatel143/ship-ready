<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC012',
    title: 'Insecure session cookie configuration',
    category: 'security',
    severity: 'high',
    productionOnly: true
)]
final class InsecureSessionCookieCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $config = $context->config();

        if ($config->get('session.secure') !== true) {
            yield $this->finding(
                message: 'session.secure is not set to true. Session cookies will be sent over HTTP, enabling hijacking.',
                fix:     'Set SESSION_SECURE_COOKIE=true in your production .env, ensuring HTTPS is enforced.'
            );
        }

        if ($config->get('session.http_only') !== true) {
            yield $this->finding(
                message: 'session.http_only is not enabled. Session cookies are accessible via JavaScript, enabling XSS-based session theft.',
                fix:     'Set session http_only to true in config/session.php.'
            );
        }

        $sameSite = $config->get('session.same_site');

        if ($sameSite === null || strtolower((string)$sameSite) === 'none') {
            yield $this->finding(
                message: "session.same_site is '{$sameSite}', which allows cross-site cookie sending. CSRF risk is elevated.",
                fix:     'Set session same_site to "lax" or "strict" in config/session.php.'
            );
        }
    }
}
