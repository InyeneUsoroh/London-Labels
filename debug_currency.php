<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain');
echo "Debugging CURRENCY_SYMBOL\n";
echo "=========================\n";
echo "Value: " . var_export(CURRENCY_SYMBOL, true) . "\n";
echo "Type: " . gettype(CURRENCY_SYMBOL) . "\n";

$constants = get_defined_constants(true);
if (isset($constants['user']['CURRENCY_SYMBOL'])) {
    echo "Found in user-defined constants.\n";
}

// Check for any suspicious environment variables
echo "\nEnvironment Variables:\n";
foreach (['CURRENCY_SYMBOL', 'CURRENCY', 'PRICE_SYMBOL'] as $env) {
    echo "$env: " . var_export(getenv($env), true) . "\n";
}

echo "\nTrace:\n";
$files = get_included_files();
foreach ($files as $file) {
    echo "Included: $file\n";
}
?>
