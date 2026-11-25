<?php
declare(strict_types=1);

/*
 * Role: Own session tool configuration, probes and object/link execution.
 * Used by: Compiler_Session, LLVM_Backend and Native_Builder
 * Call map:
 *   LLVM_Toolchain::configuration(); compile_objects(); link_objects()
 *     -> policy_fingerprint(); [action] validate/cache inputs and execute configured tools
 */

namespace prepare_backend;

use tool_process\Tool_Process;

// Session-owned cache of successful toolchain probes, independent of accepted
// program state. Failed probes are never cached. Only the coordinator invokes it.
/**
 * @compiler-api Session-owned backend tool service used by preparation, native building and compile.
 * Coordinator calls may launch configured Clang, create/remove probe files and update
 * successful-probe caches. This mutable owner is never shared with body workers.
 * No method runs the compiled user program; failures throw and are not cached.
 */
class LLVM_Toolchain
{
    private ?backend_configuration $configuration = null;
    private string $input_key = '';
    private string $clang = '';

    /** @var array<string, bool> */
    private array $verified_signatures = [];
    private int $invocations = 0;
    private ?int $native_bits = null;
    private int $compile_jobs = 1;
    private ?string $launcher = null;
    private ?string $requested_linker = null;
    private string $default_linker_context = '';
    private string $default_linker = '';
    private ?link_configuration $link = null;

    /** @compiler-api Create a session-local tool/probe owner with optional configuration path; no probes yet. */
    public function __construct(private readonly ?string $path = null)
    {
    }

    /**
     * @compiler-api Read tool configuration/executable/policy inputs and probe target facts when changed.
     * Return a verified shared configuration; successful identical inputs reuse it.
     * Policy source bytes are fingerprinted, so even comment edits invalidate this cache.
     */
    public function configuration(): backend_configuration
    {
        $path = $this->configuration_path();
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Cannot read backend toolchain configuration: ' . $path);
        }
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $error) {
            throw new \RuntimeException('Invalid backend toolchain JSON: ' . $error->getMessage(), 0, $error);
        }
        if ((!is_array($data)) || (array_diff(array_keys($data), ['clang', 'target', 'compile_jobs', 'linker']) !== []) || (!is_string($data['clang'] ?? null)) || ($data['clang'] === '')
            || (!array_key_exists('target', $data)) || (($data['target'] !== null) && ((!is_string($data['target'])) || ($data['target'] === '')))) {
            throw new \InvalidArgumentException('Backend toolchain requires clang and a target string or null');
        }
        $jobs = $data['compile_jobs'] ?? 1;
        if ((array_key_exists('compile_jobs', $data) && (!is_int($data['compile_jobs']))) || (!is_int($jobs)) || ($jobs < 1)) {
            throw new \InvalidArgumentException('compile_jobs must be a positive integer');
        }
        $this->compile_jobs = $jobs;
        if ((isset($data['linker'])) && ((!is_string($data['linker'])) || ($data['linker'] === ''))) {
            throw new \InvalidArgumentException('linker must be an executable name/path or null for the Clang default');
        }
        $this->requested_linker = $data['linker'] ?? null;

        // Scheduling and linker selection do not change IR/ABI or native object generation.
        $content = json_encode(['clang' => $data['clang'], 'target' => $data['target']], JSON_THROW_ON_ERROR);
        $clang = self::executable($data['clang'], dirname($path));
        clearstatcache(true, $clang);
        $stat = stat($clang);

        // Preparation owns the complete policy tree; tools have a separate execution fingerprint.
        $policy_key = self::policy_fingerprint();
        $key = hash('sha256', json_encode([$content, $clang, $stat['ino'], $stat['mtime'], $stat['ctime'], $stat['size'],
                    $policy_key, hash_file('sha256', __FILE__), hash_file('sha256', __DIR__ . '/../../../../tool_process/process.php')], JSON_THROW_ON_ERROR));
        if (($key === $this->input_key) && ($this->configuration !== null)) {
            return $this->configuration;
        }

        // Changed compiler or policy inputs require fresh target facts from the real toolchain.
        $version = $this->run([$clang, '--version'], '');
        if (!str_contains($version, 'clang version')) {
            throw new \RuntimeException('Configured backend executable is not Clang');
        }
        $arguments = [$clang];
        if ($data['target'] !== null) {
            $arguments[] = '--target=' . $data['target'];
        }
        $ir = $this->run([...$arguments, '-S', '-emit-llvm', '-x', 'c', '-o', '-', '-'], 'void scpp_target_probe(void) {}');
        $triple = self::field($ir, '/^target triple = "([^"\r\n]+)"$/m', 'target triple');
        $layout = self::field($ir, '/^target datalayout = "([^"\r\n]+)"$/m', 'data layout');
        $cpu = self::field($ir, '/"target-cpu"="([^"\r\n]+)"/', 'CPU');
        $features = self::field($ir, '/"target-features"="([^"\r\n]*)"/', 'features');
        $configuration = new backend_configuration('clang:' . hash('sha256', $key . $version), $triple, $layout,
            $cpu, $features, 'llvm-file-modules:' . $policy_key, 'none');

        // Adopt the verified configuration and invalidate probes that depended on the old target.
        $this->clang = $clang;
        $this->configuration = $configuration;
        $this->input_key = $key;
        $this->verified_signatures = [];
        $this->native_bits = null;
        return $configuration;
    }

    /** Fingerprint policy file membership and bytes, including new nested files; execution services stay separate. */
    private static function policy_fingerprint(): string
    {
        $root = dirname(__DIR__);
        $sources = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file)
        {
            $relative = substr($file->getPathname(), strlen($root) + 1);
            if (!$file->isFile() || ($file->getExtension() !== 'php') || str_starts_with($relative, 'tools/')) {
                continue;
            }
            $sources[$relative] = $file->getPathname();
        }

        // Native-entry adaptation is the one policy input produced outside this process owner.
        $sources['../lower/main_native_entry.php'] = $root . '/../lower/main_native_entry.php';
        ksort($sources);
        foreach ($sources as $name => $path) {
            $fingerprint = hash_file('sha256', $path);
            if ($fingerprint === false) {
                throw new \RuntimeException('Cannot fingerprint backend policy source: ' . $path);
            }
            $sources[$name] = $fingerprint;
        }
        return hash('sha256', json_encode($sources, JSON_THROW_ON_ERROR));
    }

    /**
     * @compiler-api Read active configuration and resolved compiler paths for output protection;
     * requires prior configuration preparation, otherwise throws.
     * @return list<string> Active configuration and prepared compiler/linker/process-group launcher executables.
     */
    public function input_paths(): array
    {
        if ($this->configuration === null) {
            throw new \LogicException('Toolchain inputs require a prepared configuration');
        }
        return [$this->configuration_path(), $this->clang, ...($this->launcher === null ? [] : [$this->launcher]),
            ...($this->link === null ? [] : [$this->link->executable])];
    }

    /** @compiler-api Probe a prepared ABI signature against the current target; cache successful shape/convention checks only. @param list<string> $parameters */
    public function verify_signature(string $type, array $parameters, backend_configuration $configuration): void
    {
        if ($configuration !== $this->configuration) {
            throw new \LogicException('Signature probe requires the current verified target');
        }
        $key = json_encode([$type, $parameters], JSON_THROW_ON_ERROR);
        if (isset($this->verified_signatures[$key])) {
            return;
        }
        $declarations = [];
        foreach ($parameters as $i => $parameter) {
            $declarations[] = $parameter . ' %p' . ($i + 1);
        }
        $declaration = implode(', ', $declarations);
        $operands = $declaration;

        // This is an isolated ABI capability probe, never the user's program.
        // A visible caller exercises the prepared convention during object generation.
        $return = $type === 'void' ? 'ret void' : 'ret ' . $type . ' zeroinitializer';
        $call = $type === 'void' ? 'call ' : '%v = call ';
        $caller_return = $type === 'void' ? 'ret void' : 'ret ' . $type . ' %v';
        $cc = LLVM_Backend::CALLING_CONVENTION;
        $ir = 'target triple = ' . LLVM_Types::quote($configuration->target_triple) . "\n"
            . 'target datalayout = ' . LLVM_Types::quote($configuration->data_layout) . "\n"
            . 'define ' . LLVM_Backend::LINKAGE . ' ' . $cc . ' ' . $type . " @scpp_probe($declaration) #0 {\n " . $return . "\n}\n"
            . 'define ' . $cc . ' ' . $type . " @scpp_probe_use($declaration) #0 {\n " . $call . $cc . ' ' . $type
            . " @scpp_probe($operands)\n " . $caller_return . "\n}\n"
            . 'attributes #0 = { "target-cpu"=' . LLVM_Types::quote($configuration->cpu)
            . ' "target-features"=' . LLVM_Types::quote($configuration->features) . " }\n";
        $object = tempnam(sys_get_temp_dir(), 'scpp_abi_');
        if ($object === false) {
            throw new \RuntimeException('Cannot create backend probe output');
        }
        try {
            $this->run([$this->clang, '--target=' . $configuration->target_triple, '-x', 'ir', '-c', '-o', $object, '-'], $ir);
            clearstatcache(true, $object);
            if (filesize($object) === 0) {
                throw new \RuntimeException('Backend ABI probe produced no object');
            }
        }
        finally {
            clearstatcache(true, $object);
            if (is_file($object)) {
                unlink($object);
            }
        }
        $this->verified_signatures[$key] = true;
    }

    /**
     * @compiler-api Coordinator-only capability probe for an LLVM return spelling against the exact
     * current configuration. Cache success; throws on stale target/tool/probe failure.
     */
    public function verify_return(string $type, backend_configuration $configuration): void
    {
        $this->verify_signature($type, [], $configuration);
    }

    /**
     * @compiler-api Probe/cache the supported hosted C main return width for the current target;
     * throws for stale configuration or unsupported ABI. Does not infer a language int.
     */
    public function native_return_bits(backend_configuration $configuration): int
    {
        $this->require_current($configuration);
        if ($this->native_bits !== null) {
            return $this->native_bits;
        }
        $ir = $this->run([$this->clang, '--target=' . $configuration->target_triple,
                '-S', '-emit-llvm', '-x', 'c', '-o', '-', '-'], 'int main(void) { return 0; }');

        // Accept only the ABI form implemented by Native_Entry. Targets needing
        // return attributes, a non-default convention or another symbol reject.
        $bits = self::field($ir, '/^define (?:dso_local )?i([0-9]+) @main\(\)/m', 'supported hosted main return ABI');
        return $this->native_bits = (int)$bits;
    }

    /**
     * @compiler-api Execute a fixed selected batch with at most compile_jobs live
     * external tools. Refill free slots on completion; link is a separate operation.
     * On any launch/compile/timeout failure, cancel/reap all active tools before
     * returning control to native object rollback. No accepted output is modified.
     * @param list<object_compilation> $requests
     */
    public function compile_objects(array $requests): void
    {
        $outputs = [];
        foreach ($requests as $request) {
            $this->require_current($request->configuration);
            if (isset($outputs[$request->output])) {
                throw new \LogicException('Duplicate native object output');
            }
            $outputs[$request->output] = true;
        }

        // Dispatch only after all requests have distinct outputs and current target contracts.
        $active = [];
        $next = 0;
        try
        {
            while (($next < count($requests)) || ($active !== []))
            {
                // Poll every active job before refilling, so an observed failure
                // stops new dispatch and completed jobs free their own slot.
                foreach ($active as $index => $process)
                {
                    if (!$process->ready()) {
                        continue;
                    }
                    $process->result();
                    self::require_output($requests[$index]->output);
                    $process->close();
                    unset($active[$index]);
                }

                // Fill only the available slots after every active job has been checked for failure.
                while (($next < count($requests)) && (count($active) < $this->compile_jobs)) {
                    $request = $requests[$next];
                    $active[$next] = $this->start([$this->clang, '--target=' . $request->configuration->target_triple,
                            '-O0', '-x', 'ir', '-c', '-o', $request->output, '-'], $request->ir);
                    ++$next;
                }
                if ($active !== []) {
                    usleep(1000);
                }
            }
        }
        finally {
            foreach ($active as $process) {
                $process->close();
            }
        }
    }

    /**
     * @compiler-api Compile supplied IR with configured Clang into the caller's private output path;
     * require a current target and nonempty object. Throws on failure; no publication.
     */
    public function compile_object(string $ir, string $output, backend_configuration $configuration): void
    {
        $this->compile_objects([new object_compilation($ir, $output, $configuration)]);
    }

    /**
     * @compiler-api Resolve/fingerprint the link tool for current target inputs. No object generation.
     * Null configuration follows Clang's default; an explicit executable is never silently replaced.
     * Link-only changes do not alter the backend configuration or invalidate native objects.
     */
    public function link_configuration(backend_configuration $configuration): link_configuration
    {
        $this->require_current($configuration);
        $requested = $this->requested_linker;
        if ($requested === null)
        {
            $context = hash('sha256', json_encode([$configuration, getenv('PATH')], JSON_THROW_ON_ERROR));
            if ($context !== $this->default_linker_context) {
                $this->default_linker = trim($this->run([$this->clang, '--target=' . $configuration->target_triple, '-print-prog-name=ld'], ''));
                $this->default_linker_context = $context;
            }
            $requested = $this->default_linker;
        }
        $path = self::executable($requested, dirname($this->configuration_path()));
        clearstatcache(true, $path);
        $stat = stat($path);
        $key = hash('sha256', json_encode([$configuration, $path, $stat['ino'], $stat['mtime'], $stat['ctime'], $stat['size']], JSON_THROW_ON_ERROR));
        if ($this->link?->key !== $key) {
            $this->link = new link_configuration($path, $key);
        }
        return $this->link;
    }

    /**
     * @compiler-api Link the caller's current object set to a private executable path under current target;
     * require nonempty output. Throws on failure; no publication or execution.
     * @param list<string> $objects Complete current object paths, in coordinator order.
     */
    public function link_objects(array $objects, string $output, backend_configuration $configuration, ?link_configuration $link = null,
        ?\load_runtime\Runtime_Input_Set $runtime = null): void
    {
        $this->require_current($configuration);
        $current = $this->link_configuration($configuration);
        if (($link !== null) && ($link != $current)) {
            throw new \RuntimeException('Linker changed during native preparation');
        }
        $driver = [$this->clang, '--target=' . $configuration->target_triple];
        if ($runtime !== null) {
            $this->verify_runtime($runtime, $configuration);
            $driver = [$runtime->link_driver, ...$runtime->link_arguments];
            $objects = [...$objects, ...$runtime->modules_for(\load_runtime\runtime_module_kind::ordinary)];
        }
        $this->run([...$driver, '--ld-path=' . $current->executable, ...$objects, '-o', $output], '');
        self::require_output($output);
    }

    /** @compiler-api Verify the runtime input set against the exact compiler/target used by this session. */
    public function verify_runtime(\load_runtime\Runtime_Input_Set $runtime, backend_configuration $configuration): void
    {
        $this->require_current($configuration);
        if (($runtime->target_triple !== $configuration->target_triple) || ($runtime->data_layout !== $configuration->data_layout)
            || (realpath($runtime->link_driver) !== realpath($this->clang))) {
            throw new \RuntimeException('Runtime package is incompatible with the selected compiler or target');
        }
    }

    /** @compiler-api Read instrumentation count of attempted external tool invocations; no new probe. */
    public function invocation_count(): int
    {
        return $this->invocations;
    }

    /** Capture a fixed layout-probe command and process launcher for isolated selected workers. */
    public function layout_tools(backend_configuration $configuration, bool $native = false): array
    {
        $this->require_current($configuration);
        $this->launcher ??= self::executable('setsid', getcwd());
        return [[$this->clang, '--target=' . $configuration->target_triple, '-O2', '-S', '-emit-llvm', '-x', $native ? 'c++' : 'ir', ...($native ? ['-std=c++23'] : []), '-', '-o', '-'], $this->launcher];
    }

    private function configuration_path(): string
    {
        return $this->path ?? __DIR__ . '/../../../../tools/backend.json';
    }

    private function require_current(backend_configuration $configuration): void
    {
        // Backend joins can reuse an equal previously accepted configuration after repair.
        if ($configuration != $this->configuration) {
            throw new \LogicException('Native operation requires the current verified target');
        }
    }

    private static function require_output(string $path): void
    {
        clearstatcache(true, $path);
        if ((!is_file($path)) || (filesize($path) === 0)) {
            throw new \RuntimeException('Clang produced no native output');
        }
    }

    /** Extract one unambiguous target fact from real Clang probe output. */
    private static function field(string $ir, string $pattern, string $name): string
    {
        preg_match_all($pattern, $ir, $matches);
        $values = array_values(array_unique($matches[1]));
        if (count($values) !== 1) {
            throw new \RuntimeException('Clang probe did not supply an unambiguous ' . $name);
        }
        return $values[0];
    }

    /** Resolve the configured executable against its directory or PATH, rejecting unavailable tools. */
    private static function executable(string $name, string $directory): string
    {
        $paths = str_contains($name, '/') ? [str_starts_with($name, '/') ? $name : $directory . '/' . $name]
            : array_map(static fn($part) => $part . '/' . $name, explode(PATH_SEPARATOR, getenv('PATH') ?: ''));
        foreach ($paths as $path) {
            if (is_file($path) && is_executable($path)) {
                return realpath($path);
            }
        }
        throw new \RuntimeException('Cannot locate configured backend executable: ' . $name);
    }

    private function start(array $command, string $input): Tool_Process
    {
        $this->launcher ??= self::executable('setsid', getcwd());
        ++$this->invocations;
        return new Tool_Process($command, $input, $this->launcher);
    }

    /**
     * Probes and final linking use the same process lifecycle with one active job.
     */
    private function run(array $command, string $input): string
    {
        $process = $this->start($command, $input);
        try {
            while (!$process->ready()) {
                usleep(1000);
            }
            return $process->result();
        }
        finally {
            $process->close();
        }
    }
}
