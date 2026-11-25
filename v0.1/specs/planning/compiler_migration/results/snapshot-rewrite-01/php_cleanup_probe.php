<?php
require '/home/alexv/__AI/simple_cpp/simple_cpp_01/tools/php_portability/runtime/bootstrap.php';
$path = '/tmp/scpp-snapshot-native-01/inputs/empty.phs';
$before = count(get_resources('stream'));
for ($i = 0; $i < 100; ++$i) {
    if (scpp\fs_read_snapshot($path, 1700000000, 0) !== '') { throw new Exception('Empty read failed'); }
    try { scpp\fs_read_snapshot($path, 1700000001, 0); throw new LogicException('Accepted stale version'); }
    catch (RuntimeException $expected) {}
}
if (count(get_resources('stream')) !== $before) { throw new Exception('Stream leak'); }
echo "100 successful and 100 rejected PHP reads preserve stream count\n";
