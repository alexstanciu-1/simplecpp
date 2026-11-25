<?php
// Retained source behaviors, exercised through ordinary/general concrete emission.
$program = <<<'PHS'
class Invoice {
 public int $amount = 7;
 public function touch(): void { $this->amount = $this->amount + 1; }
 public function read(int $offset = 0): int { return $this->amount + $offset; }
 public function ordered(int $first, int $second, int &$write, const int &$read): int {
  $write = $first * 10 + $second;
  return $read;
 }
 public function detach(Invoice &$owner): int { $owner = null; return $this->amount; }
 public function echo_value(value<Invoice> $input): value<Invoice> { return $input; }
 public function __destruct() { $this->touch(); }
}
class Constructed {
 public int $amount = 7;
 public function __construct(int $first = 7, int $second = 0) { $this->amount = $first + $second * 10; }
 public function read(): int { return $this->amount; }
}
struct ConstructedRow {
 public int $amount;
 public function __construct(set:$amount = 19) {}
 public function read(): int { return $this->amount; }
}
class CopyBox {
 public value<Invoice> $item;
 public int $observed = 0;
 public function __construct(value<Invoice> $input) {
  $copy = $input;
  $this->item = $copy;
  $this->observed = $input->read();
 }
 public function read(): int { return $this->observed; }
}
function constructor_failure(): void {
 $input value<Invoice>;
 $box = new CopyBox($input);
}
class Unused { public int $value; }
struct Row {
 public vector<int> $numbers = [1, 2];
 public function append(int $number): void { $this->numbers[] = $number; }
 public function size(): int { return count($this->numbers); }
}
function step(int &$order): int { $order += 1; return $order; }
function replace_receiver(Invoice &$owner): int { $owner = new Invoice(); return 3; }
function make_shared(): Invoice { return new Invoice(); }
function make_unique(): unique<Invoice> { $owner unique<Invoice> = new Invoice(); return move($owner); }
function make_value(): value<Invoice> { $owner value<Invoice>; return $owner; }
function failure(): void { $a value<Invoice>; $b = $a->echo_value($a); }
function work(): int {
 $construction_order = 0;
 $constructed = new Constructed(step($construction_order), step($construction_order));
 if ($constructed->read() !== 21) { return 20; }
 if ($construction_order !== 2) { return 21; }
 $inline value<Constructed> = new Constructed(3, 4);
 if ($inline->read() !== 43) { return 22; }
 $unique unique<Constructed> = new Constructed(5, 6);
 $defaulted = new Constructed();
 if ($defaulted->read() !== 7) { return 23; }
 $partial = new Constructed(9);
 if ($partial->read() !== 9) { return 24; }
 $row_constructed = new ConstructedRow();
 if ($row_constructed->read() !== 19) { return 27; }
 $row_explicit = new ConstructedRow(23);
 if ($row_explicit->read() !== 23) { return 28; }
 $input value<Invoice>;
 $box = new CopyBox($input);
 if ($box->read() !== 7) { return 26; }
 $a = new Invoice();
 $alias = $a;
 $list vector<Invoice> = [$a, $alias];
 $copy = $list[0];
 $nested vector<vector<Invoice>> = [[$a], [$copy]];
 $owner unique<Invoice> = new Invoice();
 $moved = move($owner);
 $value value<Invoice>;
 $cloned = $value;
 $row Row;
 $rows vector<Row> = [$row, $row];
 if (count($list) !== 2) { return 1; }
 if (count($nested) !== 2) { return 2; }
 $order = 0;
 $seen = 0;
 if ($a->ordered(step($order), step($order), $seen, $seen) !== 12) { return 3; }
 if ($order !== 2) { return 4; }
 $selected = new Invoice();
 $selected->touch();
 if ($selected->read(replace_receiver($selected)) !== 11) { return 5; }
 if ($selected->read() !== 7) { return 6; }
 $retained = new Invoice();
 if ($retained->detach($retained) !== 7) { return 7; }
 $value->touch();
 if ($value->read() !== 8) { return 8; }
 $row->append(3);
 if ($row->size() !== 3) { return 9; }
 $returned = $a->echo_value($value);
 if ($returned->read() !== 8) { return 10; }
 return 0;
}

PHS;
