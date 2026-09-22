<?php
declare(strict_types=1);
namespace load_runtime;

/** Session-owned reservation; the framework token handles final cleanup, including exception unwinding. */
final class Runtime_Lease {
    public function __construct(private \scpp\Lock_Reservation $reservation, public readonly Runtime_Package $package) {
        $this->reservation = $reservation->transfer();
    }
    public function active(): bool { return $this->reservation->active(); }
    public function release(): void { $this->reservation->release(); }
}
