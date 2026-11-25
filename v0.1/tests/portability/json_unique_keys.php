<?php
declare(strict_types=1);
require_once __DIR__ . '/../../tools/php_portability/runtime/json_document.php';

$count = 0;
foreach (file(__DIR__ . '/json_unique_keys.tsv', FILE_IGNORE_NEW_LINES) as $row) {
    [$position, $text] = explode("\t", $row, 2);
    $position = (int)$position;
    $error = null;
    try { $view = \scpp\json_read($text); }
    catch (\JsonException $failure) { $error = $failure; }
    if ($position < 0) {
        if ($error !== null) { throw $error; }
    } elseif ($error === null || !str_starts_with($error->getMessage(),
        'json error at byte ' . $position . ': duplicate object key ')) {
        throw new LogicException('Duplicate-key rejection/position mismatch: ' . $text);
    }
    $count++;
}
// Original grammar validation remains in PHP's decoder, before token inspection.
foreach (['{', '{"a":', '"unterminated', '{"a":"\\uD800"}', '{} trailing'] as $text) {
    try { \scpp\json_read($text); }
    catch (\JsonException $error) { continue; }
    throw new LogicException('Malformed input was accepted');
}
$view = \scpp\json_read('{"a":1}');
if ($view->member('a')->integer() !== 1) { throw new LogicException('Repair failed'); }
echo "PASS: $count unique-key corpus cases, malformed input and recovery\n";
