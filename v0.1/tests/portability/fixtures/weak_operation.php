<?php
final class Weak_Row { public int $number = 7; }
final class Weak_Observer {
    public Weak_Row $target /** weak<Weak_Row> */;
    public ?Weak_Row $optional /** weak<Weak_Row> */ = null;
    public int $calls = 0;
    public function source(Weak_Row $row): Weak_Row {
        $this->calls += 1;
        return $row;
    }
    public function attach(Weak_Row $row): void {
        $this->target = weak($this->source($row));
        $this->optional = $this->target;
    }
    public function read(): Weak_Row { return weakref_get($this->target); }
    public function present(): bool { return weakref_get($this->optional) !== null; }
    public function target_present(): bool { return weakref_get($this->target) !== null; }
    public function clear(): void { $this->optional = null; }
}
$row = new Weak_Row();
$observer = new Weak_Observer();
$observer->attach($row);
$held = $observer->read();
$held->number = 9;
echo $row->number, ':', $observer->calls, ':', $observer->present() ? 1 : 0, "\n";
$observer->clear();
echo $observer->present() ? 1 : 0, "\n";
