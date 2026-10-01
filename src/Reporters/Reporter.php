<?php

namespace ShipReady\Reporters;

use ShipReady\Support\Report;

interface Reporter
{
    public function render(Report $report): string;
}
