<?php
namespace hash_construction;
final class key_record { public int $value = 3; }
final class owner {
    public array $indices /** vector<int> */;
    public array $labels /** hash<string> */;
    public function reset_arrays(): void {
        $this->indices = /** vector<int> */ [];
        $this->labels = /** hash<string> */ [];
    }
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

$container->reset_arrays();
$container->indices[] = 5;
$container->labels['name'] = 'ok';
echo $container->indices[0], ':', $container->labels['name'], "\n";
$container->reset_arrays();
$container->indices[] = 8;
echo $container->indices[0], ':', isset($container->labels['name']) ? 'bad' : 'empty', "\n";
