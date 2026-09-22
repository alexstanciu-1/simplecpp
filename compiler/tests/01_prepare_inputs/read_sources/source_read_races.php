<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

// Test-only stream wrapper delegates to a real regular file, then changes it
// during stream_read. This proves read races deterministically without sleeps
// or test hooks in compiler code. No wrapper is installed in the compiler CLI.
class Read_Race_Stream
{
    public mixed $context;
    public static string $action = '';
    public static int $open_handles = 0;
    private mixed $handle;
    private string $path;
    private bool $changed = false;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->path = substr($path, strlen('scppread://'));
        $this->handle = fopen($this->path, 'rb');
        self::$open_handles++;
        return true;
    }

    public function url_stat(string $path, int $flags): array|false
    {
        $real = substr($path, strlen('scppread://'));
        clearstatcache(true, $real);
        return @lstat($real);
    }

    public function stream_stat(): array|false
    {
        return fstat($this->handle);
    }

    public function stream_eof(): bool
    {
        return feof($this->handle);
    }

    public function stream_read(int $count): string|false
    {
        $bytes = fread($this->handle, $count);
        if (!$this->changed)
        {
            $this->changed = true;
            if (self::$action === 'grow') {
                file_put_contents($this->path, 'extra', FILE_APPEND);
            }
            elseif (self::$action === 'replace') {
                $stat = fstat($this->handle);
                rename($this->path, $this->path . '.old');
                file_put_contents($this->path, str_repeat('x', $stat['size']));
                touch($this->path, $stat['mtime']);
            }
            elseif (self::$action === 'remove') {
                unlink($this->path);
            }
        }
        return $bytes;
    }

    public function stream_close(): void
    {
        fclose($this->handle);
        self::$open_handles--;
    }
}

stream_wrapper_register('scppread', Read_Race_Stream::class);
try
{
    $path = realpath('../fixtures/three_files') . '/read-race.phs';
    foreach (['grow', 'replace', 'remove'] as $action)
    {
        file_put_contents($path, 'return 42;');
        clearstatcache(true, $path);
        $stat = stat($path);
        Read_Race_Stream::$action = $action;
        $task = new \read_sources\source_read_task(1, 'scppread://' . $path, $stat['mtime'], $stat['size']);
        $rejected = false;
        try {
            \read_sources\Snapshot_Reader::read($task);
        }
        catch (Exception $exception) {
            $rejected = str_contains($exception->getMessage(), 'Source changed during reading');
        }
        if ((!$rejected) || (Read_Race_Stream::$open_handles !== 0)) {
            throw new Exception('Read race must fail and close its handle: ' . $action);
        }
    }
}
finally {
    stream_wrapper_unregister('scppread');
}
echo "source read races ok: mid-read growth, same-metadata path replacement, deletion and handle release\n";
