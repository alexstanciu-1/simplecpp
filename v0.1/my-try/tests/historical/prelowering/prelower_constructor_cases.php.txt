<?php
// Extend the same replay/counter proof with positional source construction.
$program = str_replace('class Unused', <<<'PHS'
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
class Unused
PHS, $program);
$program = str_replace('function work(): int {', <<<'PHS'
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
PHS, $program);
