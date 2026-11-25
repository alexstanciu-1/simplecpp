<?php
// Retained prototype agreement; each document is read through its real file API.
define('collect_symbols\\MAX_SYMBOL_ID', 4294967295);
require dirname(__DIR__, 2) . '/reference/pre-rewrite/src/04_analyze/instantiate/policy.php';
$cases = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
foreach ($cases as $case) {
    $path = tempnam(sys_get_temp_dir(), 'instance-policy-');
    try {
        file_put_contents($path, $case['text']);
        $actual = 0;
        try { $actual = \instantiate\Instantiation_Policy::load($path); }
        catch (\RuntimeException|\JsonException $error) {}
        if ($actual !== $case['value']) { throw new \LogicException('Retained policy differs'); }
    } finally { unlink($path); }
}
echo count($cases), " retained policy cases passed\n";
