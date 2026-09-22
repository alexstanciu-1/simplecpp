<?php
declare(strict_types=1);
require __DIR__ . '/../../compiler/bootstrap.php';
require __DIR__ . '/oracles/binary_syntax_original.php';
$alphabet = [tokenize\token_kind::left_angle, tokenize\token_kind::right_angle,
    tokenize\token_kind::left_parenthesis, tokenize\token_kind::right_parenthesis,
    tokenize\token_kind::left_bracket, tokenize\token_kind::right_bracket,
    tokenize\token_kind::semicolon, tokenize\token_kind::left_brace,
    tokenize\token_kind::right_brace, tokenize\token_kind::variable_name];
mt_srand(20260921);
for ($trial = 0; $trial < 5000; ++$trial) {
    $tokens = new tokenize\Token_Buffer(new read_sources\Source_Buffer(1, 'oracle', 0, ''));
    for ($i = 0, $length = mt_rand(0, 150); $i < $length; ++$i) {
        $row = new tokenize\token(); $row->kind = $alphabet[mt_rand(0, count($alphabet) - 1)];
        $tokens->rows[] = $row;
    }
    $expected = parse\Baseline_Binary_Syntax::angle_ends($tokens);
    $actual = parse\Binary_Syntax::angle_ends($tokens);
    if ($actual !== $expected) { throw new RuntimeException('Angle mismatch at trial ' . $trial); }
}
echo "5000 scoped-angle streams match frozen prototype\n";
