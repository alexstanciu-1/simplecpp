<?php
namespace additive_proof;

final class Row {
    public int $offset /** uint32 */ = 7;
}

final class Probe {
    public int $calls = 0;
    private Row $item;

    public function __construct(Row $item) {
        $this->item = $item;
    }

    public function row(): Row {
        $this->calls++;
        return $this->item;
    }

    public function index(): int {
        $this->calls++;
        return 0;
    }
}

$value = 8;
$value += 5;
$value -= 15;
$row = new Row();
$offset /** uint32 */ = 5;
$decrement /** uint32 */ = 2;
$step /** uint32 */ = 3;
$row->offset += $offset;
$row->offset -= $decrement;
$probe = new Probe($row);
$probe->row()->offset += $step;
$items /** vector<int> */ = [6];
$items[$probe->index()] -= 2;
echo $value, ':', $row->offset, ':', $items[0], ':', $probe->calls;
