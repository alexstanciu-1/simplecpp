<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Immutable relation membership at one reachable block entry. */
final class Allocation_Entry {
    private array $rows /** hash<int> */ = [];
    public function __construct(array $states /** hash<int> */) {
        foreach ($states as $key => $state) {
            if (($state < 0) || ($state > 15)) { throw new \LogicException('Invalid allocation entry relation'); }
            $this->rows[$key] = $state;
        }
    }
    public function states(): array /** hash<int> */ { return $this->rows; }
}
/** Validated allocation facts retain their exact checked body; full lifetime acceptance remains separate. */
final class Allocation_Analysis {
    private array $entry_rows /** hash<Allocation_Entry,int> */ = [];
    public function __construct(public readonly \check_bodies\Checked_Body $body, array $entries /** hash<Allocation_Entry,int> */) {
        if (!isset($entries[1])) { throw new \LogicException('Allocation analysis lacks entry block'); }
        foreach ($entries as $id => $entry) {
            if (($id < 1) || ($id > $body->block_count())) { throw new \LogicException('Invalid allocation entry block'); }
            $this->entry_rows[$id] = $entry;
        }
    }
    public function entries(): array /** hash<Allocation_Entry,int> */ { return $this->entry_rows; }
}
