<?php
declare(strict_types=1);
require dirname(__DIR__,3).'/tests/portability/oracles/byte_literals_original.php';
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
$rows=[];
foreach($cases as $input){
    try{$expected=bin2hex(\check_bodies\Baseline_Byte_Literals::decode($input));$valid=true;}
    catch(\InvalidArgumentException $error){$expected=$error->getMessage();$valid=false;}
    $rows[]=['hex'=>bin2hex($input),'valid'=>$valid,'expected'=>$expected];
}
echo json_encode($rows,JSON_THROW_ON_ERROR),"\n";
