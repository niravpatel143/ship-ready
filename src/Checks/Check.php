<?php

namespace ShipReady\Checks;

use ShipReady\Support\Context;

interface Check
{
    /**
     * Run the check and yield any findings.
     *
     * @return iterable<\ShipReady\Support\Finding>
     */
    public function run(Context $context): iterable;
}
