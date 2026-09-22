<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Own fixed Clang inputs, dependency discovery and concrete compilation jobs. */
final class Clang_Toolchain
{
    /** @var list<string> */
    private readonly array $command;
    public readonly string $executable;
    public readonly string $version;
    private readonly string $target;
    private readonly string $launcher;
    /** @var list<list<string>> */
    public array $commands = [];

    /**
     * Verify the configured Clang driver and capture fixed target, language and include arguments.
     * @param array<string, mixed> $config
     */
    public function __construct(array $config, string $base)
    {
        $this->executable = Files::executable($base, $config['clang']);
        $this->launcher = Files::executable($base, 'setsid');
        $this->version = trim($this->run([$this->executable, '--version']));
        if (!preg_match('/clang version \d+\./', $this->version)) {
            throw new \RuntimeException('Configured compiler must be Clang');
        }
        if (!preg_match('/^Target: (\S+)$/m', $this->version, $target)) {
            throw new \RuntimeException('Clang did not report its default target');
        }
        $this->target = $config['target'] ?? $target[1];
        $command = [$this->executable, '-std=' . $config['standard'], '-fPIC', '-x', 'c++'];
        if ($config['target'] !== null) {
            $command[] = '--target=' . $config['target'];
        }
        foreach ($config['include_directories'] as $directory) {
            $command[] = '-I' . Files::path($base, $directory);
        }
        $this->command = $command;
    }

    /**
     * Rediscover the bridge's transitive headers and decode Clang's escaped Make dependency paths.
     * @return list<string>
     */
    public function dependencies(string $source): array
    {
        // -M includes system headers and re-resolves include paths on every run,
        // so newly shadowing headers cannot hide behind the previous dependency set.
        $output = $this->run([...$this->command, '-M', '-MT', 'runtime', '-'], $source);
        $output = str_replace("\\\n", '', $output);
        if (!str_starts_with($output, 'runtime:')) {
            throw new \RuntimeException('Unrecognized Clang dependency output');
        }
        $words = [];
        $word = '';
        $escaped = false;
        foreach (str_split(substr($output, 8)) as $character)
        {
            if ($escaped) {
                $word .= $character;
                $escaped = false;
            }
            elseif ($character === '\\') {
                $escaped = true;
            }
            elseif (ctype_space($character)) {
                if ($word !== '') {
                    $words[] = str_replace('$$', '$', $word);
                    $word = '';
                }
            }
            else {
                $word .= $character;
            }
        }
        if ($word !== '') {
            $words[] = str_replace('$$', '$', $word);
        }
        if (($escaped) || ($words === [])) {
            throw new \RuntimeException('Incomplete Clang dependency output');
        }
        return array_values(array_unique($words));
    }

    public function llvm(string $source): string
    {
        return $this->run([...$this->command, '-O0', '-S', '-emit-llvm', '-o', '-', '-'], $source);
    }

    public function bitcode(string $llvm, string $output): void
    {
        $this->run([$this->executable, '--target=' . $this->target, '-x', 'ir', '-c', '-emit-llvm', '-o', $output, '-'], $llvm);
    }

    public function lto(string $source, string $mode, string $output): void
    {
        $this->run([...$this->command, '-O2', '-flto=' . $mode, '-c', '-emit-llvm', '-o', $output, '-'], $source);
    }

    public function inspect_bitcode(string $path): string
    {
        return $this->run([$this->executable, '--target=' . $this->target, '-x', 'ir', '-S', '-emit-llvm', '-o', '-', $path]);
    }

    /** Prove that the generated calls have definitions using the driver's C++ runtime. */
    public function verify_link(string $bitcode, string $target, string $output): void
    {
        $this->run([$this->executable, '--driver-mode=g++', '--target=' . $target, '-fPIC', '-shared',
            '-Wl,--no-undefined', $bitcode, '-o', $output]);
    }

    public function declarations(string $source, string $name): string
    {
        return $this->run([...$this->command, '-fsyntax-only', '-Xclang', '-ast-dump=json',
            '-Xclang', '-ast-dump-filter=' . $name, '-'], $source);
    }

    /**
     * Record and execute one bounded tool command; always release its process and private streams.
     * @param list<string> $command
     */
    public function run(array $command, string $input = ''): string
    {
        $this->commands[] = $command;
        $process = new \tool_process\Tool_Process($command, $input, $this->launcher, 60.0);
        try {
            while (!$process->ready()) {
                usleep(10000);
            }
            return $process->result();
        }
        finally {
            $process->close();
        }
    }
}
