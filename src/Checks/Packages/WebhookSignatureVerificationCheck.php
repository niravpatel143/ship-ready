<?php

namespace ShipReady\Checks\Packages;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PAY001',
    title: 'Webhook route missing signature verification middleware',
    category: 'packages',
    severity: 'critical'
)]
final class WebhookSignatureVerificationCheck extends AbstractCheck
{
    private const WEBHOOK_PATTERNS = [
        'stripe'   => ['stripe', 'payment', 'webhook'],
        'paddle'   => ['paddle'],
        'braintree' => ['braintree'],
        'paypal'   => ['paypal'],
        'mollie'   => ['mollie'],
        'cashier'  => ['cashier'],
    ];

    private const SIGNATURE_MIDDLEWARE = [
        'VerifyWebhookSignature',
        'stripe.webhook',
        'paddle.webhook',
        'signed',
        'webhook.verify',
        'VerifyStripeWebhook',
        'CheckSignature',
    ];

    public function run(Context $context): iterable
    {
        $routeFiles = [
            base_path('routes/web.php'),
            base_path('routes/api.php'),
        ];

        foreach ($routeFiles as $routeFile) {
            if (!file_exists($routeFile)) {
                continue;
            }

            $content = file_get_contents($routeFile) ?: '';

            // Find routes that look like payment webhooks
            $hasWebhookRoute = false;

            foreach (self::WEBHOOK_PATTERNS as $provider => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match('/[\'"].*?' . preg_quote($pattern, '/') . '.*?webhook.*?[\'"]/', $content, $m)
                        || preg_match('/[\'"].*?webhook.*?' . preg_quote($pattern, '/') . '.*?[\'"]/', $content, $m)
                    ) {
                        $hasWebhookRoute = true;

                        // Check if any signature middleware is present near this route
                        $hasSignatureCheck = false;

                        foreach (self::SIGNATURE_MIDDLEWARE as $mw) {
                            if (str_contains($content, $mw)) {
                                $hasSignatureCheck = true;
                                break;
                            }
                        }

                        if (!$hasSignatureCheck) {
                            yield $this->finding(
                                message: "A {$provider} webhook route was found without signature verification middleware. Anyone can forge webhook calls to trigger payment actions.",
                                file:    $routeFile,
                                fix:     "Add VerifyWebhookSignature middleware to your webhook route, or use spatie/laravel-webhook-client which handles signature verification automatically."
                            );
                        }

                        break 2;
                    }
                }
            }
        }

        // Also check controller files that handle webhooks without verification
        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            if (!preg_match('/Webhook.*Controller|.*WebhookController/i', basename($file))) {
                continue;
            }

            $content = file_get_contents($file) ?: '';

            $hasSignatureCheck = false;

            foreach (self::SIGNATURE_MIDDLEWARE as $mw) {
                if (str_contains($content, $mw)) {
                    $hasSignatureCheck = true;
                    break;
                }
            }

            if (!$hasSignatureCheck && !str_contains($content, 'hash_equals') && !str_contains($content, 'hmac')) {
                yield $this->finding(
                    message: basename($file, '.php') . ' is a webhook controller but does not verify the payload signature.',
                    file:    $file,
                    fix:     'Verify the webhook signature using hash_equals(hash_hmac(...), $request->header(\'X-Signature\')), or use spatie/laravel-webhook-client.'
                );
            }
        }
    }
}
