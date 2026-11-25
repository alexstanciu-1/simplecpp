<?php
declare(strict_types=1);
namespace runtime_preparation;

/** Own one exclusive lease and discard private output unless publication succeeds. */
final class Package_Candidate
{
    private bool $published = false;
    private readonly string $manifest_hash;
    private readonly ?string $previous_pointer;

    /** Fixed build inputs are retained only until acceptance/publication completes. */
    public function __construct(public readonly string $output, public readonly string $directory,
        public readonly bool $built, private mixed $lock, public readonly string $configuration,
        public readonly string $configuration_hash, public readonly Definitions $definitions,
        public readonly Bridge $bridge, public readonly Clang_Toolchain $toolchain,
        public readonly array $inputs, public readonly string $input_key, public readonly ?string $request_contract = null, public readonly ?project\module_contract $project = null)
    {
        $this->manifest_hash = hash('sha256', Files::read($directory . '/manifest.json'));
        $this->previous_pointer = is_file($output . '/current.json') ? Files::read($output . '/current.json') : null;
    }

    /** Exact definition checks happen on private output as well as reused packages. */
    public function validate(): array
    {
        $this->require_open();
        return Prepared_Request::validate($this->directory, $this->definitions, $this->bridge->provider,
            $this->input_key, $this->manifest_hash, $this->project, $this->toolchain);
    }

    /** Recheck inputs and artifacts before replacing the stable package; never publish partial metadata. */
    public function publish(): array
    {
        $this->require_open();
        $this->validate();
        $current = (new Runtime_Preparation())->inputs($this->configuration, $this->configuration_hash,
            $this->definitions, $this->bridge, $this->toolchain, $this->request_contract);
        if ($current !== $this->inputs) {
            throw new \RuntimeException('Preparation inputs changed before publication');
        }
        $pointer = is_file($this->output . '/current.json') ? Files::read($this->output . '/current.json') : null;
        if ($pointer !== $this->previous_pointer) {
            throw new \RuntimeException('Publication changed after candidate selection');
        }
        if ($this->built) {
            Publication::publish($this->output, $this->directory, $this->input_key);
        }
        $this->published = true;
        Publication::cleanup_publication($this->output);
        return ['status' => $this->built ? 'built' : 'reused', 'input_key' => $this->input_key,
            'manifest' => $this->output . '/package/manifest.json', 'clang_commands' => count($this->toolchain->commands)];
    }

    private function require_open(): void
    {
        if (!is_resource($this->lock) || ($this->published)) {
            throw new \LogicException('Preparation candidate is closed');
        }
    }

    /** Failed candidates leave the old package untouched; always release the reservation. */
    public function release(): void
    {
        try {
            if (($this->built) && (!$this->published) && is_dir($this->directory)) {
                Files::remove_tree($this->directory);
            }
        }
        finally {
            if (is_resource($this->lock)) {
                flock($this->lock, LOCK_UN);
                fclose($this->lock);
                $this->lock = null;
            }
        }
    }

    public function __destruct()
    {
        $this->release();
    }
}
