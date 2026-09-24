<?php
// Host-PHP reference comparison, not a PHS/converter fixture.
declare(strict_types=1);
$helpers = $argv[1] ?? throw new InvalidArgumentException('Pass the compiler helpers directory');
require_once $helpers . '/storage_abstract.php';
require_once $helpers . '/storage.php';
require_once $helpers . '/keyed_storage.php';
use scpp\compiler\Storage;
use scpp\compiler\Keyed_Storage;
function rejection(string $type, Closure $operation): void {
    try { $operation(); echo "accepted\n"; }
    catch (Throwable $e) { if (!($e instanceof $type)) throw $e; echo "rejected\n"; }
}
$a = (object)['value' => 10]; $b = (object)['value' => 20];
$list = new Storage(4);
echo $list->append($a), ',', $list->append($b), ',', $list->append($a), "\n";
$alias = $list; $retained = $list[0];
$alias[0]->value = 11; $alias[0] = $b;
$list->remove(1); unset($list[-1], $list[80]); $list->reserve(100);
$position = $list->append($a);
echo $position, ':', count($list), ':', (int)$list->is_empty(), ':', (int)isset($list[-1]), "\n";
foreach ($list as $k => $r) echo $k, '=', $r->value, "\n";
echo 'retained=', $retained->value, "\n";
rejection(OutOfBoundsException::class, fn() => $list[1]);
rejection(OutOfBoundsException::class, fn() => $list->replace(1, $a));
rejection(OutOfBoundsException::class, fn() => $list->remove(1));
rejection(InvalidArgumentException::class, function () use ($list) { $list[] = null; });
rejection(InvalidArgumentException::class, fn() => new Storage(-1));
$map = new Keyed_Storage(4);
foreach (['', '0', '00', "a\0b", 'a'] as $k) $map->add($k, $a);
$map['0'] = $b; $map->replace('a', $b); $map->remove(''); $map[''] = $a;
unset($map['absent']); $map->reserve(100);
foreach ($map as $key => $r) echo bin2hex($key), '=', $r->value, "\n";
echo count($map), ':', (int)$map->is_empty(), ':', (int)isset($map['absent']), "\n";
rejection(InvalidArgumentException::class, fn() => $map->add('0', $a));
rejection(OutOfBoundsException::class, fn() => $map->replace('absent', $a));
rejection(OutOfBoundsException::class, fn() => $map->remove('absent'));
rejection(OutOfBoundsException::class, fn() => $map['absent']);
rejection(InvalidArgumentException::class, function () use ($map) { $map['absent'] = null; });
rejection(InvalidArgumentException::class, fn() => new Keyed_Storage(-1));
