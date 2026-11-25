<?php
declare(strict_types=1);
namespace scpp;

final class Process_Output {
    public string $stdout_text = '';
    public string $stderr_text = '';
    public int $exit_code = -1;
    public int $signal = 0;
    public bool $timed_out = false;
    public bool $stopped = false;
}

/** One token owns the proc resource; proc_close is the sole reaper. */
final class Process_Handle {
    private mixed $process = null;
    private array $streams = [];
    private int $pid = 0;
    private int $owner = 0;
    private bool $group_ready = false;
    private bool $done = false;
    private bool $lost = false;
    private int $started = 0;
    private int $timeout_ms = 0;
    private Process_Output $status;
    private ?Process_Output $cached = null;
    private bool $closed = false;
    private function __construct() { $this->status = new Process_Output(); }
    private function __clone() {}

    private static function temporary_stream(): mixed {
        $path = tempnam('/tmp', 'scpp-process-');
        if ($path === false) { throw new \RuntimeException('Cannot create process stream'); }
        try {
            chmod($path, 0600);
            $stream = fopen($path, 'w+e');
            if ($stream === false) { throw new \RuntimeException('Cannot open process stream'); }
            return $stream;
        } finally { unlink($path); }
    }

    public static function start(string $executable, array $args, string $input, int $timeout_ms, string $cwd): self {
        if (PHP_OS_FAMILY !== 'Linux' || !function_exists('pcntl_waitid') || !function_exists('posix_setsid')) {
            throw new \RuntimeException('Processes require Linux PHP 8.4+ with PCNTL and POSIX');
        }
        if (pcntl_signal_get_handler(SIGCHLD) !== SIG_DFL) { throw new \RuntimeException('Processes require default SIGCHLD'); }
        if ($executable === '' || $executable[0] !== '/' || str_contains($executable, "\0") || $timeout_ms < 0
            || ($cwd !== '' && ($cwd[0] !== '/' || str_contains($cwd, "\0"))) || !array_is_list($args)) {
            throw new \RuntimeException('Invalid process arguments');
        }
        foreach ($args as $arg) {
            if (!is_string($arg) || str_contains($arg, "\0")) { throw new \RuntimeException('Invalid process argument'); }
        }
        $handle = new self();
        $handle->owner = getmypid();
        $handle->timeout_ms = $timeout_ms;
        $directory = '/tmp/scpp-launch-' . bin2hex(random_bytes(16));
        if (!mkdir($directory, 0700)) { throw new \RuntimeException('Cannot create launch directory'); }
        $control = null; $sentinel = null; $ack = null;
        try {
            for ($i = 0; $i < 3; ++$i) { $handle->streams[] = self::temporary_stream(); }
            $offset = 0;
            while ($offset < strlen($input)) {
                $written = fwrite($handle->streams[0], substr($input, $offset));
                if ($written === false || $written === 0) { throw new \RuntimeException('Cannot write process input'); }
                $offset += $written;
            }
            rewind($handle->streams[0]);
            if (!posix_mkfifo($directory . '/control', 0600) || !posix_mkfifo($directory . '/ack', 0600)) { throw new \RuntimeException('Cannot create launch channel'); }
            // A temporary writer lets us open the reader without blocking before spawn.
            $ack = fopen($directory . '/ack', 'r+e');
            $sentinel = fopen($directory . '/control', 'r+e');
            $control = fopen($directory . '/control', 're');
            if ($ack === false || $sentinel === false || $control === false) { throw new \RuntimeException('Cannot open launch channel'); }
            stream_set_blocking($control, false);
            $config = serialize(['executable'=>$executable,'args'=>$args,'cwd'=>$cwd,'control'=>$directory.'/control','ack'=>$directory.'/ack']);
            if (file_put_contents($directory.'/config', $config) !== strlen($config)) { throw new \RuntimeException('Cannot write launch configuration'); }
            $handle->process = proc_open([PHP_BINARY, '-r', 'require $argv[1];', __DIR__.'/process_launcher.php', $directory.'/config'],
                [0=>$handle->streams[0],1=>$handle->streams[1],2=>$handle->streams[2]], $pipes);
            if (!is_resource($handle->process)) { throw new \RuntimeException('Cannot launch process shim'); }
            // Target execution is gated by ack: this cannot reap a completed target.
            $initial = proc_get_status($handle->process);
            $handle->pid = $initial['pid'];
            if (!$initial['running']) { $handle->lost = true; throw new \RuntimeException('Launch shim exited before readiness'); }
            $header = '';
            while (!str_contains($header, "\n")) {
                $part = fread($control, 256);
                if ($part === false) { throw new \RuntimeException('Cannot read launch channel'); }
                $header .= $part;
                if (strlen($header) > 256) { throw new \RuntimeException('Invalid launch frame'); }
                if (str_contains($header, "\n")) { break; }
                $info = $handle->observe();
                if (($info['pid'] ?? 0) !== 0) { throw new \RuntimeException('Launch shim exited before readiness'); }
                usleep(1000);
            }
            if ($header !== 'READY '.$handle->pid."\n" || posix_getpgid($handle->pid) !== $handle->pid) {
                throw new \RuntimeException('Invalid process readiness');
            }
            $handle->group_ready = true;
            fclose($sentinel); $sentinel = null;
            stream_set_blocking($control, true);
            if ($ack === false || fwrite($ack, 'G') !== 1) { throw new \RuntimeException('Cannot acknowledge launch'); }
            fclose($ack); $ack = null;
            $tail = stream_get_contents($control, 4096);
            if ($tail === false || $tail !== '') { throw new \RuntimeException('Process setup/exec failed'); }
            $handle->started = hrtime(true);
            return $handle;
        } catch (\Throwable $error) {
            $handle->close();
            throw $error;
        } finally {
            foreach ([$ack,$control,$sentinel] as $stream) { if (is_resource($stream)) { fclose($stream); } }
            foreach (['config','control','ack'] as $name) { if (file_exists($directory.'/'.$name)) { unlink($directory.'/'.$name); } }
            rmdir($directory);
        }
    }

    private function require_owner(): void {
        if ($this->closed || $this->owner !== getmypid()) { throw new \RuntimeException('Process is closed or inherited'); }
        if ($this->lost) { throw new \RuntimeException('Process wait ownership lost'); }
    }

    private function observe(): array {
        do {
            $ok = pcntl_waitid(P_PID, $this->pid, $info, WEXITED | WNOHANG | WNOWAIT);
        } while (!$ok && pcntl_get_last_error() === PCNTL_EINTR);
        if (!$ok) {
            if (pcntl_get_last_error() === PCNTL_ECHILD) { $this->lost = true; }
            throw new \RuntimeException('Cannot observe owned process');
        }
        return $info;
    }

    private function signal_group(): void {
        if ($this->lost || $this->done || !is_resource($this->process)) { return; }
        $id = $this->group_ready ? -$this->pid : $this->pid;
        if (!@posix_kill($id, SIGKILL) && posix_get_last_error() !== 3) {
            throw new \RuntimeException('Cannot stop owned process');
        }
    }

    public function poll(): bool {
        $this->require_owner();
        if ($this->done) { return true; }
        $info = $this->observe();
        if (($info['pid'] ?? 0) !== 0) {
            $this->signal_group();
            $this->status->exit_code = $info['code'] === CLD_EXITED ? $info['status'] : -1;
            $this->status->signal = $info['code'] === CLD_EXITED ? 0 : $info['status'];
            proc_close($this->process); $this->process = null; $this->done = true;
            return true;
        }
        if (!$this->status->stopped && !$this->status->timed_out && $this->timeout_ms > 0
            && (hrtime(true)-$this->started)/1000000 >= $this->timeout_ms) {
            $this->signal_group(); $this->status->timed_out = true;
        }
        return false;
    }

    public function stop(): void {
        if ($this->poll()) { return; }
        if (!$this->status->timed_out && !$this->status->stopped) {
            $this->signal_group(); $this->status->stopped = true;
        }
    }

    public function output(): Process_Output {
        $this->require_owner();
        if (!$this->done) { throw new \RuntimeException('Process is not complete'); }
        if ($this->cached === null) {
            $copy = clone $this->status;
            foreach ([1=>'stdout_text',2=>'stderr_text'] as $index=>$field) {
                $size = fstat($this->streams[$index]);
                if ($size === false || !rewind($this->streams[$index])) { throw new \RuntimeException('Cannot inspect process output'); }
                $bytes = $size['size'] === 0 ? '' : stream_get_contents($this->streams[$index], $size['size']);
                if ($bytes === false || strlen($bytes) !== $size['size']) { throw new \RuntimeException('Cannot read process output'); }
                $copy->$field = $bytes;
            }
            $this->cached = $copy;
        }
        return clone $this->cached;
    }

    public function close(): void {
        if ($this->closed) { return; }
        if ($this->owner !== getmypid()) { throw new \RuntimeException('Process is inherited'); }
        if (is_resource($this->process)) {
            if (!$this->lost) { $this->observe(); $this->signal_group(); }
            proc_close($this->process); $this->process = null;
        }
        foreach ($this->streams as $stream) { if (is_resource($stream)) { fclose($stream); } }
        $this->streams = []; $this->cached = null; $this->closed = true;
    }

    public function __destruct() {
        try {
            if ($this->owner === getmypid()) { $this->close(); }
            else { foreach ($this->streams as $stream) { if (is_resource($stream)) { fclose($stream); } } }
        } catch (\Throwable) { /* Nonthrowing last-owner fallback; explicit close reports errors. */ }
    }
}

function process_spawn(string $executable, array $args, string $input, int $timeout_ms, string $cwd): Process_Handle {
    return Process_Handle::start($executable,$args,$input,$timeout_ms,$cwd);
}
function process_poll(Process_Handle $handle): bool { return $handle->poll(); }
function process_output(Process_Handle $handle): Process_Output { return $handle->output(); }
function process_stop(Process_Handle $handle): void { $handle->stop(); }
function process_close(Process_Handle $handle): void { $handle->close(); }
