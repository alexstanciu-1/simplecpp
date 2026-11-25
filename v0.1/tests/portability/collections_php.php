<?php
declare(strict_types=1);

// Additional host behavior proof; converted/native cases live in collections.py.
require __DIR__ . '/../../tools/php_portability/runtime/collections.php';

function verify_collection(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}

$input = [10, 20, 30];
$above = static fn(int $value): bool => $value > 10;
verify_collection(scpp\sequence_filter($input, $above) === [20, 30], 'dense sequence');
verify_collection(scpp\keyed_filter($input, $above) === [1 => 20, 2 => 30], 'dense integer hash retains keys');
verify_collection($input === [10, 20, 30], 'input unchanged');
$suffix = '!';
$label = static function (int $value) use ($suffix): string { return $value . $suffix; };
verify_collection(scpp\sequence_map($input, $label) === ['10!', '20!', '30!'], 'map result type and capture');
$keyed = ['b' => 20, 'a' => 10, '04' => 30];
verify_collection(scpp\keyed_map($keyed, $label) === ['b' => '20!', 'a' => '10!', '04' => '30!'], 'key order and string key');
verify_collection(scpp\keyed_filter($keyed, $above) === ['b' => 20, '04' => 30], 'keyed filter');
verify_collection(scpp\keyed_map([9 => 10, -2 => 20], $label) === [9 => '10!', -2 => '20!'], 'sparse integer keys');

foreach (['sequence', 'keyed'] as $policy) {
    $map = 'scpp\\' . $policy . '_map';
    $filter = 'scpp\\' . $policy . '_filter';
    $never = static function (int $value): int { throw new RuntimeException('Empty callback invoked'); };
    verify_collection($map([], $never) === [], 'empty map');
    verify_collection($filter([], static function (int $value): bool { throw new RuntimeException('Empty predicate invoked'); }) === [], 'empty filter');
    verify_collection($filter($input, static fn(int $value): bool => true) === $input, 'all retained');
    verify_collection($filter($input, static fn(int $value): bool => false) === [], 'none retained');
    // Instrumentation deliberately observes callback calls; application callbacks stay pure.
    $visited = [];
    $map($input, static function (int $value) use (&$visited): int { $visited[] = $value; return $value; });
    verify_collection($visited === $input, 'once per element in order');
    $cause = new RuntimeException('callback error');
    $published = 'unchanged';
    try {
        $published = $map($input, static function (int $value) use ($cause): int {
            if ($value === 20) { throw $cause; }
            return $value;
        });
        throw new LogicException('Expected exception');
    } catch (RuntimeException $error) {
        verify_collection($error === $cause && $published === 'unchanged', 'exception identity, no partial output');
    }
    try {
        $filter($input, static fn(int $value): int => 1);
        throw new LogicException('Expected predicate rejection');
    } catch (UnexpectedValueException $error) {}
}

foreach (['scpp\\sequence_map', 'scpp\\sequence_filter'] as $operation) {
    try {
        $operation([1 => 10], static fn(int $value): bool => true);
        throw new LogicException('Expected sequence shape rejection');
    } catch (InvalidArgumentException $error) {}
}

$row = new stdClass();
$row->value = 3;
$rows = [$row];
$selected = scpp\sequence_filter($rows, static fn(stdClass $value): bool => true);
verify_collection($selected[0] === $row, 'ordinary object identity retained');
$selected[] = new stdClass();
verify_collection(count($rows) === 1, 'independent outer membership');
echo "PHP collections: explicit policies, ordering, empties, captures, identity and failures passed\n";
