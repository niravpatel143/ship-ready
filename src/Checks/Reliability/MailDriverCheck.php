<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL005',
    title: 'Mail driver set to log or array in production',
    category: 'reliability',
    severity: 'high',
    productionOnly: true
)]
final class MailDriverCheck extends AbstractCheck
{
    private const DEV_MAILERS = ['log', 'array', 'null'];

    public function run(Context $context): iterable
    {
        $mailer = $context->config()->get('mail.default');

        if ($mailer === null) {
            $mailer = $context->config()->get('mail.driver');
        }

        if (in_array($mailer, self::DEV_MAILERS, true)) {
            yield $this->finding(
                message: "Mail driver is set to '{$mailer}', which does not deliver real emails. Transactional emails will be silently dropped.",
                fix:     "Set MAIL_MAILER=smtp (or ses/mailgun/postmark) in your production .env."
            );
        }
    }
}
