<?php
declare(strict_types=1);
namespace scpp;

/** Dedicated shared token; resources are never exposed to authored compiler code. */
final class File_Lock {
    private mixed $stream = null;
    private int $owner = 0;
    private function __clone() {}

    public static function acquire(self &$out, string $path, bool $shared): bool {
        if (PHP_OS_FAMILY !== 'Linux') { throw new \RuntimeException('File locks require Linux'); }
        if ($out->stream !== null) { throw new \RuntimeException('Lock output must be empty or released'); }
        if ($path === '' || str_contains($path, "\0")) { throw new \RuntimeException('Invalid lock path'); }
        // Stable path identity is an application contract. Reject existing special files
        // before PHP's blocking fopen (which has no O_NONBLOCK open option).
        $existing = @stat($path);
        if ($existing !== false && ($existing['mode'] & 0170000) !== 0100000) {
            throw new \RuntimeException('Lock path must be a regular file');
        }
        // e requests O_CLOEXEC; c never truncates an existing file.
        $stream = @fopen($path, $shared ? 're' : 'c+e');
        if ($stream === false) { throw new \RuntimeException('Cannot open lock file'); }
        try {
            $stat = fstat($stream);
            if ($stat === false || ($stat['mode'] & 0170000) !== 0100000) {
                throw new \RuntimeException('Lock path must be a regular file');
            }
            $blocked = 0;
            if (!@flock($stream, ($shared ? LOCK_SH : LOCK_EX) | LOCK_NB, $blocked)) {
                if ($blocked !== 0) { return false; }
                throw new \RuntimeException('Cannot acquire file lock');
            }
            $token = new self();
            $token->stream = $stream;
            $token->owner = getmypid();
            $out = $token;
            $stream = null;
            return true;
        } finally { if (is_resource($stream)) { fclose($stream); } }
    }

    public function release(): void {
        if ($this->stream === null) { return; }
        $this->require_owner();
        if (!@flock($this->stream, LOCK_UN)) { throw new \RuntimeException('Cannot release file lock'); }
        $stream = $this->stream;
        $this->stream = null;
        if (!@fclose($stream)) { throw new \RuntimeException('Cannot close file lock'); }
    }

    public function transfer(): self {
        $this->require_owner();
        $next = new self();
        $next->stream = $this->stream;
        $next->owner = $this->owner;
        $this->stream = null;
        return $next;
    }

    private function require_owner(): void {
        if ($this->stream === null || $this->owner !== getmypid()) {
            throw new \RuntimeException('Lock is empty, released or inherited');
        }
    }

    public function __destruct() {
        if ($this->stream === null) { return; }
        if ($this->owner === getmypid()) { @flock($this->stream, LOCK_UN); }
        @fclose($this->stream);
    }
}

function lock_empty(): File_Lock { return new File_Lock(); }
function lock_try(File_Lock &$out, string $path, bool $shared): bool { return File_Lock::acquire($out, $path, $shared); }
function lock_release(File_Lock $handle): void { $handle->release(); }
function lock_transfer(File_Lock $handle): File_Lock { return $handle->transfer(); }
