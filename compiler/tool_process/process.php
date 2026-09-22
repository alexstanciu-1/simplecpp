<?php
declare(strict_types=1);

/*
 * Role: Own one external process and its bounded cleanup.
 * Used by: LLVM_Toolchain; runtime preparation Clang_Toolchain; layout workers
 * Call map:
 *   Tool_Process::__construct(); ready(); result(); close()
 *     -> [action] start, poll, collect output and stop remaining descendants
 */
namespace tool_process;

/**
 * @compiler-internal Linux external-tool process owner. No PHP worker or shared
 * compiler-data serialization. Private file streams avoid pipe deadlocks; a new
 * process group permits timeout/failure cleanup of the driver and descendants.
 */
final class Tool_Process
{
    private $process = null;
    private $in = null;
    private $out = null;
    private $err = null;
    private int $pid = 0;
    private int $deadline = 0;
    private ?int $exit_code = null;

    /**
     * Start one isolated process group with private streams and a deadline; close partial resources on failure.
     * @param list<string> $command
     */
    public function __construct(array $command, string $input, string $launcher, float $timeout = 10.0)
    {
        if ((PHP_OS_FAMILY !== 'Linux') || (!function_exists('posix_kill'))) {
            throw new \RuntimeException('Native process execution currently requires Linux and PHP POSIX support');
        }
        try
        {
            // File-backed streams let tools write output without blocking on undrained pipes.
            $this->in = tmpfile();
            $this->out = tmpfile();
            $this->err = tmpfile();
            if (($this->in === false) || ($this->out === false) || ($this->err === false)) {
                throw new \RuntimeException('Cannot allocate toolchain streams');
            }
            if (fwrite($this->in, $input) !== strlen($input)) {
                throw new \RuntimeException('Cannot write tool input');
            }
            rewind($this->in);

            // proc_open's child inherits our group, so setsid creates a session
            // whose group ID is this child PID before executing the configured tool.
            $this->process = proc_open([$launcher, '--', ...$command], [0 => $this->in, 1 => $this->out, 2 => $this->err], $pipes);
            if (!is_resource($this->process)) {
                throw new \RuntimeException('Cannot start configured tool');
            }
            $status = proc_get_status($this->process);
            $this->pid = $status['pid'];
            if (!$status['running']) {
                $this->exit_code = $status['exitcode'];
            }
            $this->deadline = hrtime(true) + (int)($timeout * 1e9);
        }
        catch (\Throwable $error) {
            $this->close();
            throw $error;
        }
    }

    /** Poll without waiting; record completion once (including older PHP exit-status semantics). */
    public function ready(): bool
    {
        if ($this->exit_code !== null) {
            return true;
        }
        $status = proc_get_status($this->process);
        if (!$status['running']) {
            $this->exit_code = $status['exitcode'];
            return true;
        }
        if (hrtime(true) >= $this->deadline) {
            throw new \RuntimeException('Clang backend operation timed out');
        }
        return false;
    }

    /** Consume successful output or report stderr. Caller always closes the owner. */
    public function result(): string
    {
        if (!$this->ready()) {
            throw new \LogicException('Tool result is not ready');
        }
        rewind($this->err);
        if ($this->exit_code !== 0) {
            throw new \RuntimeException('Clang backend operation failed: ' . trim(stream_get_contents($this->err)));
        }
        rewind($this->out);
        return stream_get_contents($this->out);
    }

    /** Cancel/reap before output owners are discarded. Idempotent on all paths. */
    public function close(): void
    {
        // Stop the entire tool process group before releasing streams or private outputs.
        if (is_resource($this->process))
        {
            if ($this->pid > 0) {
                @posix_kill(-$this->pid, 9);
            }
            if (proc_get_status($this->process)['running']) {
                proc_terminate($this->process, 9);
            }
            proc_close($this->process);
            $this->process = null;
        }
        foreach (['in', 'out', 'err'] as $name) {
            if (is_resource($this->$name)) {
                fclose($this->$name);
            }
            $this->$name = null;
        }
    }

    private function __clone()
    {
    }

    public function __destruct()
    {
        $this->close();
    }
}
