<?php
// Execute the retained unit body and capture every scan as a portable outcome case.
require __DIR__ . '/reference_support.php';
$reference = dirname(__DIR__, 2) . '/reference/pre-rewrite';
require $reference . '/src/02_tokenize/structures.php';
require $reference . '/src/02_tokenize/tokenize.php';
$cases = [];
function capture_scan(string $text): \tokenize\Token_Buffer {
    global $cases;
    try {
        $buffer = \tokenize\File_Tokenizer::tokenize(new \read_sources\Source_Buffer(7, 'lexical-test.phs', 0, $text));
        $cases[] = ['text'=>base64_encode($text),'rows'=>array_map(fn($t)=>[$t->kind->name,$t->start,$t->length],$buffer->rows)];
        return $buffer;
    } catch (\diagnostics\Source_Error $error) {
        $cases[] = ['text'=>base64_encode($text),'error'=>[$error->start,$error->length]];
        throw $error;
    }
}
$test = file_get_contents($reference . '/tests/02_tokenize/tokenization.php');
$test = str_replace("require_once __DIR__ . '/../support/bootstrap.php';", '', $test);
$test = str_replace("return \\tokenize\\File_Tokenizer::tokenize(new Source_Buffer(7, 'lexical-test.phs', 0, \$text));", 'return capture_scan($text);', $test);
ob_start();
eval(substr($test, 5));
$unit_output = ob_get_clean();
file_put_contents($argv[1], json_encode(['unit_output'=>$unit_output,'cases'=>$cases], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo $unit_output;
