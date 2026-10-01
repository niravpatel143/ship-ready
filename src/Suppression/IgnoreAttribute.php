<?php

namespace ShipReady\Suppression;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class IgnoreAttribute
{
    public string $checkId;
    public string $reason;

    public function __construct(string $checkId, string $reason = '')
    {
        $this->checkId = $checkId;
        $this->reason  = $reason;
    }
}
