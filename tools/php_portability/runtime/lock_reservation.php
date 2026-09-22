<?php
declare(strict_types=1);
namespace scpp;

/** Named shared reservation for compiler fields. Transfer invalidates all aliases of the old reservation. */
final class Lock_Reservation {
    private File_Lock $token;
    private bool $owned = false;
    private function __clone() {}
    public function __construct() { $this->token = lock_empty(); }
    public function active(): bool { return $this->owned; }
    public function acquire(string $path, bool $shared): bool {
        if ($this->owned) { throw new \RuntimeException('Reservation is already active'); }
        $token = lock_empty();
        if (!lock_try($token,$path,$shared)) { return false; }
        $this->token = $token;
        $this->owned = true;
        return true;
    }
    public function release(): void {
        if (!$this->owned) { return; }
        lock_release($this->token);
        $this->owned = false;
    }
    public function transfer(): Lock_Reservation {
        if (!$this->owned) { throw new \RuntimeException('Reservation is not active'); }
        $next = new Lock_Reservation();
        $next->token = lock_transfer($this->token);
        $next->owned = true;
        $this->owned = false;
        return $next;
    }
}
