<?php

// This is a fixture file that simulates a config with debug enabled.
// It is NOT a real config file — just used for reference in tests.

return [
    'debug' => true,
    'env'   => 'production',
    'key'   => 'base64:' . base64_encode(random_bytes(32)),
    'name'  => 'TestApp',
    'url'   => 'https://example.com',
];
