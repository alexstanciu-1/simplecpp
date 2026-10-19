<?php
// Host-side source payload cases, consumed by the existing expression proof.
$source_program = <<<'PHS'
class Invoice {
 public int $amount = 7;
 public function touch(): void { $this->amount = $this->amount + 1; }
 public function __destruct() { $this->touch(); }
}
class Unused { public int $value; }
struct Row { public vector<int> $numbers = [1, 2]; }
function make_shared(): Invoice { return new Invoice(); }
function make_unique(): unique<Invoice> { $owner unique<Invoice> = new Invoice(); return move($owner); }
function make_value(): value<Invoice> { $owner value<Invoice>; return $owner; }
function failure(): void { $a value<Invoice>; $b = $a; }
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
 return 0;
}
measure_begin();
$answer = work();
measure_end();
return $answer;
PHS;
