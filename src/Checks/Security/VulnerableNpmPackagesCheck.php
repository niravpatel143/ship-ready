<?php

namespace ShipReady\Checks\Security;

use ShipReady\Analyzers\DependencyAnalyzer;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;
use ShipReady\Support\Severity;

#[CheckMeta(
    id: 'SEC004',
    title: 'Vulnerable npm packages detected',
    category: 'security',
    severity: 'high'
)]
final class VulnerableNpmPackagesCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $analyzer        = new DependencyAnalyzer();
        $vulnerabilities = $analyzer->npmAudit();

        foreach ($vulnerabilities as $vuln) {
            $cveInfo = $vuln->cve ? " ({$vuln->cve})" : '';

            yield $this->findingWithSeverity(
                message: "Vulnerable npm package: {$vuln->package} — {$vuln->advisory}{$cveInfo}",
                severity: Severity::fromString($vuln->severity),
                fix: "Run `npm audit fix` or update {$vuln->package} to a non-vulnerable version."
            );
        }
    }
}
