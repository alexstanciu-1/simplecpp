<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/helpers/storage_abstract.php';
require_once dirname(__DIR__) . '/helpers/storage.php';
require_once dirname(__DIR__) . '/helpers/keyed_storage.php';

function check(bool $value): void
{
	if (!$value) {
		throw new \RuntimeException('Storage assertion failed');
	}
}
/** Require the operation to fail with the requested exception family. */
function rejects(\Closure $operation, string $type): void
{
	try {
		$operation();
	}
	catch (\Throwable $error) {
		check($error instanceof $type);
		return;
	}
	throw new \RuntimeException('Expected rejection: ' . $type);
}

$items = new Storage(8);
check($items->is_empty() && count($items) === 0);
$row = (object) ['value' => 3];
check($items->append($row) === 0);
$items[] = $row;
$alias = $items;
$retrieved = $items[0];
$retrieved->value = 7;
check($items[1]->value === 7 && $row === $items[0]);
$replacement = (object) ['value' => 9];
$items[0] = $replacement;
check($alias[0] === $replacement && $retrieved === $row && $retrieved->value === 7);
$items->remove(1);
check(count($items) === 1 && !isset($items[1]) && $row->value === 7);
check($items->append($row) === 2);
$items->reserve(100);
check(array_keys(iterator_to_array($items)) === [0, 2]);
$pairs = 0;
foreach ($items as $outer) {
	foreach ($items as $inner) {
		$pairs++;
	}
}
check($pairs === 4);
unset($items[99]);
check(count($items) === 2);
rejects(fn() => $items[99], \OutOfBoundsException::class);
rejects(fn() => $items->replace(99, $row), \OutOfBoundsException::class);
rejects(fn() => $items->remove(99), \OutOfBoundsException::class);
rejects(fn() => $items->offsetSet(null, 4), \InvalidArgumentException::class);
rejects(fn() => $items->offsetSet(null, null), \InvalidArgumentException::class);
foreach (['0', false, 0.0, null] as $offset) {
	rejects(fn() => $items->offsetGet($offset), \InvalidArgumentException::class);
	rejects(fn() => $items->offsetExists($offset), \InvalidArgumentException::class);
	rejects(fn() => $items->offsetUnset($offset), \InvalidArgumentException::class);
}
rejects(fn() => $items->reserve(-1), \InvalidArgumentException::class);
$items->remove(0);
unset($items[2]);
check($items->is_empty() && $items->append($row) === 3);
$limit = new \ReflectionProperty(Storage::class, 'next_position');
$limit->setValue($items, PHP_INT_MAX);
rejects(fn() => $items->append($row), \OverflowException::class);
check(count($items) === 1 && $items[3] === $row);
echo "Storage: object identity, shared membership, replacement/removal, positions and validation passed\n";

$named = new Keyed_Storage(4);
check($named->is_empty());
$named->add('first', $row);
$named['0'] = $row;
$named[''] = $replacement;
$named["\0key"] = $row;
$keys = [];
foreach ($named as $key => $record) {
	$keys[] = $key;
}
check($keys === ['first', '0', '', "\0key"]);
check($named['0'] === $row && $named['first'] === $row);
$held = $named['first'];
$held->value = 11;
check($named['0']->value === 11);
$named['first'] = $replacement;
check($held === $row && $named['first'] === $replacement);
rejects(fn() => $named->add('first', $row), \InvalidArgumentException::class);
rejects(fn() => $named['missing'], \OutOfBoundsException::class);
rejects(fn() => $named->replace('missing', $row), \OutOfBoundsException::class);
rejects(fn() => $named->remove('missing'), \OutOfBoundsException::class);
foreach ([0, false, 0.0, null] as $key) {
	rejects(fn() => $named->offsetGet($key), \InvalidArgumentException::class);
	rejects(fn() => $named->offsetSet($key, $row), \InvalidArgumentException::class);
	rejects(fn() => $named->offsetExists($key), \InvalidArgumentException::class);
	rejects(fn() => $named->offsetUnset($key), \InvalidArgumentException::class);
}
rejects(fn() => $named->offsetSet('bad', null), \InvalidArgumentException::class);
$named->remove('first');
unset($named['missing']);
$named['first'] = $row;
$keys = [];
foreach ($named as $key => $record) {
	$keys[] = $key;
}
check($keys === ['0', '', "\0key", 'first']);
check(count($named) === 4 && $held === $row);
check($items instanceof Storage_Abstract && $named instanceof Storage_Abstract);
echo "Keyed_Storage: string identity, order, shared records, replacement and validation passed\n";
