<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/../php/input.php';
require_once __DIR__ . '/../php/target.php';

function rejects(callable $action, string $fragment = ''): void {
    try {
        $action();
    }
    catch (\RuntimeException $error) {
        requireThat(str_contains($error->getMessage(), $fragment), $error->getMessage());
        return;
    }
    throw new \RuntimeException('Expected rejection');
}
$sample = decode('{"numbers":[18446744073709551615,-0,1.2300e+99,1e9999],"text":"\\u0000\\uD83D\\uDE00"}', 'fixture');
foreach (['18446744073709551615', '-0', '1.2300e+99', '1e9999'] as $i => $expected) {
    requireThat($sample->numbers[$i] instanceof JsonNumber && $sample->numbers[$i]->spelling === $expected, 'Number spelling changed');
}
requireThat($sample->text === "\0😀", 'Decoded string changed');
foreach (['1.0', '1e0', '-0', '18446744073709551615'] as $token) {
    rejects(fn() => unsigned(decode($token, 'fixture')));
}
foreach (['{"a":1,"\\u0061":2}', '{"x":{"a":1,"a":2}}', '"\\ud800"', '[] null', '[1,]', '01', '{', "\"\xc0\xaf\"", '"\\q"', "\"a\nb\""] as $input) {
    rejects(fn() => decode($input, 'fixture.json'), 'fixture.json: byte');
}
$nested = str_repeat('[', 128) . 'null' . str_repeat(']', 128);
decode($nested, 'fixture');
rejects(fn() => decode('[' . $nested . ']', 'fixture'), 'nesting limit');
requireThat(decode('{"0":null,"1":false}', 'fixture') instanceof \stdClass, 'Object/list distinction lost');
requireThat(decode('[null,false]', 'fixture') === [null, false], 'Null/false distinction lost');

$factsText = readFile($argv[1]);
$contextText = readFile($argv[2]);
$facts = targetFacts(decode($factsText, 'facts'), decode($contextText, 'context'));
requireThat($facts['fixed_integers']['uint8']['digits'] === 8, 'Fixed integer facts lost');
foreach ([ [['schema'], 2], [['context_id'], str_repeat('f', 64)], [['pointer_bytes'], 4], [['double', 'digits'], 24], [['ownership', 'shared', 'alignment'], 0], [['ownership', 'shared', 'alignment'], 3], [['ownership', 'unique', 'size'], PHP_INT_MAX], [['fixed_integers', 'int8', 'digits'], 8], [['native_integers', 'int', 'signed'], false], [['surprise'], true], ] as [$path, $value]) {
    $bad = decode($factsText, 'facts');
    $node = $bad;
    foreach (array_slice($path, 0, - 1) as $key) {
        $node = $node->{$key};
    }
    $node->{$path[count($path) - 1]} = $value;
    rejects(fn() => targetFacts($bad, decode($contextText, 'context')));
}
$bad = decode($contextText, 'context');
$bad->configuration->target = 'aarch64-linux-gnu';
rejects(fn() => targetFacts(decode($factsText, 'facts'), $bad));
// Native ownership layouts are supplied by the target, never PHP object sizes.
$changed = decode($factsText, 'facts');
$changed->ownership->shared->size = 32;
$changed->ownership->shared->alignment = 16;
$prepared = targetFacts($changed, decode($contextText, 'context'));
requireThat($prepared['ownership']['shared'] === ['size' => 32, 'alignment' => 16], 'Target layout not retained');
echo "JSON and native target input contracts passed\n";
