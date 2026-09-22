<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bin/bootstrap.php';

use Scpp\S2S\Transpiler;
use Scpp\S2S\Support\GenerationException;

function check(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
foreach (['strict', 'legacy'] as $profile) {
    foreach (['\\A\\B\\X' => '::scpp::A::B::X', 'X' => 'X', '\\X' => '::scpp::X', '\\other\\X' => '::scpp::other::X'] as $sourceName => $cppName) {
        $source = 'namespace A\\B; class X {} function make(): X { return new ' . $sourceName . '(); }';
        $result = (new Transpiler(phpProfile: $profile))->transpile('/tmp/construction.phs', sourceOverride: $source);
        check($result->errors === [], $profile . ' incorrectly rejected ' . $sourceName . ': ' . implode('; ', $result->errors));
        check(str_contains(implode("\n", $result->sourceLines), 'create<' . $cppName . '>'), 'class identity lost for ' . $sourceName);
    }
    $rejected = false;
    try {
        (new Transpiler(phpProfile: $profile))->transpile('/tmp/relative-construction.phs', sourceOverride: 'namespace A\\B; class X {} function make(): X { return new A\\B\\X(); }');
    } catch (GenerationException $error) {
        $rejected = str_contains($error->getMessage(), 'Qualified self-reference construction is rejected');
    }
    check($rejected, 'relative duplicated namespace must remain rejected');
}
echo "PASS: construction qualification and relative-name validation in strict/legacy\n";
