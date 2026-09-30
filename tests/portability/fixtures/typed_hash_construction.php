<?php
namespace hash_construction;
final class key_record { public int $value = 3; }
final class owner {
    public \SplObjectStorage $items /** hash<int, shared<key_record>> */;
    public function __construct() {
        $this->items = new \SplObjectStorage /** hash<int, shared<key_record>> */();
    }
    public function reset(): void {
        $this->items = new \SplObjectStorage /** hash<int, shared<key_record>> */();
    }
    public function fresh(): \SplObjectStorage /** hash<int, shared<key_record>> */ {
        return new \SplObjectStorage /** hash<int, shared<key_record>> */();
    }
}
$record = new key_record();
$container = new owner();
$container->items[$record] = 7;
echo $container->items[$record], ':';
$container->reset();
echo isset($container->items[$record]) ? 'bad' : 'empty', ':';
$local /** hash<int, shared<key_record>> */ = $container->fresh();
$local[$record] = 9;
echo $local[$record], "\n";
