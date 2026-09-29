<?php
namespace additive_proof;

final class Row {
    public int $offset = 7;
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
$offset = 5;
$row->offset += $offset;
$row->offset -= 2;
$probe = new Probe($row);
$probe->row()->offset += 3;
$items /** vector<int> */ = [6];
$items[$probe->index()] -= 2;
echo $value, ':', $row->offset, ':', $items[0], ':', $probe->calls;
