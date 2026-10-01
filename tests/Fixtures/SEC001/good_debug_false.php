<?php

// This is a fixture file that simulates a config with debug disabled.
// It is NOT a real config file — just used for reference in tests.

return [
    'debug' => false,
    'env'   => 'production',
    'key'   => 'base64:' . base64_encode(random_bytes(32)),
    'name'  => 'TestApp',
    'url'   => 'https://example.com',
];
