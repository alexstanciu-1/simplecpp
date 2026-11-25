<?php
declare(strict_types=1);
namespace runtime_preparation;

/** Exclusive package reservation, transferred once to a staged candidate. */
final class Package_Reservation
{
    private mixed $lock = null;

    public function __construct(public readonly string $output)
    {
    }

    /** Fail immediately on a reader or another writer; no shared-to-exclusive upgrade. */
    public function acquire(): void
    {
        if (is_resource($this->lock)) {
            throw new \LogicException('Package is already reserved');
        }
        Files::directory($this->output);
        $lock = fopen($this->output . '/.prepare.lock', 'c');
        if (($lock === false) || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new \RuntimeException('Runtime preparation output is locked');
        }
        $this->lock = $lock;
    }

    /** The candidate becomes the sole owner of release after this transfer. */
    public function take(string $output): mixed
    {
        if (!is_resource($this->lock) || (realpath($output) !== realpath($this->output))) {
            throw new \LogicException('Missing or wrong package reservation');
        }
        $lock = $this->lock;
        $this->lock = null;
        return $lock;
    }

    public function release(): void
    {
        if (is_resource($this->lock)) {
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
            $this->lock = null;
        }
    }

    public function __destruct()
    {
        $this->release();
    }
}
