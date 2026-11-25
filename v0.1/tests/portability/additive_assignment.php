<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$map = require __DIR__ . '/../../tools/php_portability/function_map.php';
$converter = new scpp\portability\Converter($map);
$source = file_get_contents(__DIR__ . '/fixtures/additive_assignment.php');
$output = $converter->convert($source, 'additive_assignment.php');
foreach (['$value += 5;', '$value -= 15;', '$row->offset += $offset;', '$row->offset -= $decrement;', '$probe->row()->offset += $step;', '$items[$probe->index()] -= 2;'] as $expected) {
    if (!str_contains($output, $expected)) {
        throw new LogicException('Compound assignment must remain intact: ' . $expected);
    }
}
ob_start();
require __DIR__ . '/fixtures/additive_assignment.php';
$trace = ob_get_clean();
if ($trace !== '-2:13:4:2') {
    throw new LogicException('Wrong values or repeated receiver/index evaluation: ' . $trace);
}
// This slice does not silently admit other mutation families.
foreach (['*=', '/=', '%=', '??='] as $operator) {
    try {
        $converter->convert('<?php $value = 8; $value ' . $operator . ' 2;', 'rejected.php');
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'rejected.php')) {
            throw new LogicException('Missing source location');
        }
        continue;
    }
    throw new LogicException('Unexpected operator acceptance: ' . $operator);
}
echo "Additive assignment: spelling, signed/compact values and single receiver/index evaluation passed\n";
