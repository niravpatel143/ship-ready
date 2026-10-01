<?php

namespace ShipReady\Checks\Security;

use ShipReady\Analyzers\DependencyAnalyzer;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;
use ShipReady\Support\Severity;

#[CheckMeta(
    id: 'SEC003',
    title: 'Vulnerable Composer packages detected',
    category: 'security',
    severity: 'high'
)]
final class VulnerableComposerPackagesCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $analyzer        = new DependencyAnalyzer();
        $vulnerabilities = $analyzer->composerAudit();

        foreach ($vulnerabilities as $vuln) {
            $cveInfo = $vuln->cve ? " ({$vuln->cve})" : '';

            yield $this->findingWithSeverity(
                message: "Vulnerable package: {$vuln->package} — {$vuln->advisory}{$cveInfo}",
                severity: Severity::fromString($vuln->severity),
                fix: "Run `composer update {$vuln->package}` or check for a patched version."
            );
        }
    }
}
