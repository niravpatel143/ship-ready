<?php

namespace ShipReady\Reporters;

use ShipReady\Support\Report;

final class JunitReporter implements Reporter
{
    public function render(Report $report): string
    {
        $findings = $report->newFindings();
        $failures = count($findings);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $suites = $dom->createElement('testsuites');
        $dom->appendChild($suites);

        $suite = $dom->createElement('testsuite');
        $suite->setAttribute('name', 'ShipReady');
        $suite->setAttribute('tests', (string)max(1, $failures));
        $suite->setAttribute('failures', (string)$failures);
        $suite->setAttribute('time', (string)round($report->duration(), 3));
        $suites->appendChild($suite);

        if (empty($findings)) {
            $testcase = $dom->createElement('testcase');
            $testcase->setAttribute('name', 'ShipReady Audit');
            $testcase->setAttribute('classname', 'ShipReady');
            $testcase->setAttribute('time', '0');
            $suite->appendChild($testcase);
        } else {
            foreach ($findings as $finding) {
                $testcase = $dom->createElement('testcase');
                $testcase->setAttribute('name', '[' . $finding->checkId . '] ' . $finding->message);
                $testcase->setAttribute('classname', 'ShipReady.' . ucfirst($this->categoryFromId($finding->checkId)));
                $testcase->setAttribute('time', '0');

                $location = '';

                if ($finding->file !== null) {
                    $location = $finding->file;

                    if ($finding->line !== null) {
                        $location .= ':' . $finding->line;
                    }
                }

                $failure = $dom->createElement('failure');
                $failure->setAttribute('message', $finding->message);
                $failure->setAttribute('type', $finding->severity->value());

                $text = $finding->message;

                if ($location) {
                    $text .= "\n\nFile: " . $location;
                }

                if ($finding->fix !== null) {
                    $text .= "\n\nFix: " . $finding->fix;
                }

                $failure->appendChild($dom->createTextNode($text));
                $testcase->appendChild($failure);
                $suite->appendChild($testcase);
            }
        }

        return $dom->saveXML();
    }

    private function categoryFromId(string $id): string
    {
        if (strncmp($id, 'SEC', 3) === 0) {
            return 'security';
        }

        if (strncmp($id, 'PERF', 4) === 0) {
            return 'performance';
        }

        if (strncmp($id, 'REL', 3) === 0) {
            return 'reliability';
        }

        return 'other';
    }
}
