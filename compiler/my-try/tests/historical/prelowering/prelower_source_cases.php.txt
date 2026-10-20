<?php
// Host-side source payload cases, consumed by the existing expression proof.
$source_program = <<<'PHS'
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
measure_begin();
$answer = work();
measure_end();
return $answer;
PHS;
