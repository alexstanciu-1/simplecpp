<?php
declare(strict_types=1);
require __DIR__ . '/../../compiler/bootstrap.php';
require __DIR__ . '/oracles/byte_literals_original.php';
function decode_outcome(string $class, string $input): array {
    try { return ['bytes', $class::decode($input)]; }
    catch (InvalidArgumentException $e) { return ['error', $e->getMessage()]; }
}
$cases = ['', "'", '"', 'plain', "'a\"", '"$name"', '"${x}"', '"$é"', '"\\u{41}"'];
foreach (["'", '"'] as $quote) {
    for ($byte = 0; $byte < 256; ++$byte) {
        $cases[] = $quote . chr($byte) . $quote;
        $cases[] = $quote . '\\' . chr($byte) . $quote;
        $cases[] = $quote . '$' . chr($byte) . $quote;
    }
    for ($value = 0; $value < 512; ++$value) {
        $cases[] = $quote . '\\' . sprintf('%03o', $value) . '9' . $quote;
        $cases[] = $quote . '\\x' . dechex($value) . 'G' . $quote;
    }
}
mt_srand(20260922);
$alphabet = ['a', 'é', '\\', '$', '{', '0', '7', '8', 'x', 'A', 'f', 'u', 'n', "'", '"', "\0", "\xff"];
for ($trial = 0; $trial < 5000; ++$trial) {
    $quote = ($trial % 2 === 0) ? '"' : "'";
    $body = '';
    for ($i = 0, $n = mt_rand(0, 50); $i < $n; ++$i) { $body .= $alphabet[mt_rand(0, count($alphabet)-1)]; }
    $cases[] = $quote . $body . $quote;
}
foreach ($cases as $id => $input) {
    $expected = decode_outcome(check_bodies\Baseline_Byte_Literals::class, $input);
    $actual = decode_outcome(check_bodies\Byte_Literals::class, $input);
    if ($expected !== $actual) { throw new RuntimeException('Byte decoder mismatch: ' . $id . ':' . bin2hex($input)); }
}
for ($value = 0; $value < 256; ++$value) {
    if (scpp\string_byte_from_int($value) !== chr($value)) { throw new RuntimeException('Byte adapter mismatch'); }
}
foreach ([-1, 256, PHP_INT_MIN, PHP_INT_MAX] as $value) {
    try { scpp\string_byte_from_int($value); throw new RuntimeException('Accepted invalid byte'); }
    catch (InvalidArgumentException $e) { if ($e->getMessage() !== 'Byte value must be between 0 and 255') { throw $e; } }
}
echo count($cases), " quoted-byte inputs match frozen prototype; all byte values and range errors proved\n";
