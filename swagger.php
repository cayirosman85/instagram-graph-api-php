<?php

require 'vendor/autoload.php';

header('Content-Type: application/json');

// Scan your API source files (modify the path if needed)
$openapi = \OpenApi\Generator::scan([__DIR__ . '/src/Instagram']);

// Output the generated OpenAPI JSON
echo $openapi->toJson();
