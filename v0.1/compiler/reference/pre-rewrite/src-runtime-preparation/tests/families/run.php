<?php
declare(strict_types=1);
namespace runtime_preparation\tests;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/src/01_prepare_inputs/load_runtime/family_adapter.php';
use runtime_preparation as native;
use runtime_preparation\families as family;

/** Standalone proof of typed declarations and transactional specialization preparation. */
final class Family_Test
{
    private int $checks = 0;
    private array $context;
    private array $catalog;
    private native\Clang_Toolchain $toolchain;

    public function __construct(private readonly string $workspace)
    {
    }

    /** Exercise supported behavior and failure boundaries through the real preparation owners. */
    public function run(): void
    {
        $base = dirname(__DIR__, 2);
        $config = native\Files::json($base . '/config.json');
        $this->context = ['clang' => $config['clang'], 'target' => $config['target'], 'standard' => $config['standard'],
            'include_directories' => [...array_map(static fn($path) => realpath(native\Files::path($base, $path)),
                $config['include_directories']), __DIR__]];
        $this->toolchain = new native\Clang_Toolchain($config, $base);
        $this->catalog = native\Files::json(__DIR__ . '/catalog.json');
        native\Files::directory($this->workspace);
        $this->contracts();

        $preparation = new family\Preparation(new family\Store($this->workspace . '/store'));
        $request = $this->request('sequence', ['signed32'], ['append_copy']);
        $first = $preparation->run($request);
        $this->check($first['status'] === 'built', 'first request builds');
        $package = dirname($first['manifest']);
        $before = native\Files::hashes(glob($package . '/*'));
        $reuse = $preparation->run($request);
        $this->check(($reuse['status'] === 'reused') && ($reuse['clang_commands'] === 3)
            && (native\Files::hashes(glob($package . '/*')) === $before), 'zero-generation reuse');
        $old = array_column(native\Files::json($package . '/metadata.json')['operations'], null, 'id');
        $extended_request = $this->request('sequence', ['signed32'], ['length']);
        $extended = $preparation->run($extended_request);
        $this->check(($extended['status'] === 'built') && ($extended['manifest'] === $first['manifest']), 'stable coverage extension');
        $new = array_column(native\Files::json($package . '/metadata.json')['operations'], null, 'id');
        $this->check(count($new) === 4, 'coverage union retains append');
        foreach ($old as $id => $operation) {
            $this->check($new[$id] === $operation, 'unchanged operation identity and ABI');
        }
        $subset = $preparation->run($request);
        $this->check($subset['status'] === 'reused', 'superset satisfies smaller demand');
        $this->execute($package, false, 'sequence');

        // A live reader prevents any replacement or input rewrite, without deadlocking.
        $output = dirname($package);
        $pointer = native\Files::read($output . '/current.json');
        $lock = fopen($output . '/.prepare.lock', 'r');
        flock($lock, LOCK_SH);
        $this->reject(fn() => $preparation->run($this->request('sequence', ['signed32'], ['read_copy'])), 'reader lease conflict');
        flock($lock, LOCK_UN);
        fclose($lock);
        $this->check(native\Files::read($output . '/current.json') === $pointer, 'busy request preserves publication');

        // Validate entire private batches before publication; order does not define identity.
        $task1 = $preparation->select($this->request('holder', ['signed32', 'signed64'], ['difference']));
        $task2 = $preparation->select($this->request('holder', ['signed64', 'signed32'], ['difference']));
        $result1 = family\Preparation::execute($task1);
        $result2 = family\Preparation::execute($task2);
        try
        {
            $join = new family\Join([$task1, $task2]);
            $this->reject(fn() => $join->join([$result1]), 'missing result');
            $this->reject(fn() => $join->join([$result1, $result1]), 'duplicate result');
            $this->reject(fn() => (new family\Join([$task1]))->join([$result2]), 'foreign result');
            $this->reject(fn() => $join->join([new family\preparation_result(clone $task1, $result1->candidate), $result2]),
                'stale selected task');
            $this->reject(fn() => $join->join([new family\preparation_result($task1, $result2->candidate), $result2]),
                'wrong candidate context');
            $accepted = $join->join([$result2, $result1]);
            $this->check(count($accepted) === 2, 'reversed result order');
            $this->check($task1->key !== $task2->key, 'ordered arguments have distinct identity');
            $published1 = $result1->candidate->publish();
            $published2 = $result2->candidate->publish();
        }
        finally {
            $result1->candidate->release();
            $result2->candidate->release();
        }
        $this->execute(dirname($published1['manifest']), true, 'holder-forward');
        $this->execute(dirname($published2['manifest']), true, 'holder-reversed');
        $this->candidate_failures($preparation, $output, $pointer);
        fwrite(STDOUT, 'Family preparation: ' . $this->checks . " checks passed\n");
    }

    /** Reject malformed declarations before Clang, including permissions not provided by bare T. */
    private function contracts(): void
    {
        $catalog = family\Catalog::parse($this->catalog, 'simple_cpp');
        $definitions = array_map(static fn($binding) => $binding->semantic, array_values($catalog->families));
        $this->check(count(\load_runtime\Family_Adapter::accept($definitions)) === 2, 'ABI-free compiler adapter');
        $holder = $catalog->families['holder']->semantic;
        $parameters = $holder->operations['initialize']->signature->parameters;
        $this->check(($parameters[0]->type->slot === 0) && ($parameters[1]->type->slot === 1)
            && ($parameters[0]->type->owner === $holder->key()), 'formal owner and ordered slots');
        $this->reject(fn() => family\Requests::arguments($catalog, $catalog->families['holder'], ['signed32']), 'wrong arity');
        $this->reject(fn() => family\Requests::arguments($catalog, $catalog->families['sequence'], ['nothing']), 'ineligible void');
        $this->reject(fn() => family\Requests::coverage($catalog->families['sequence'], ['absent']), 'unknown operation');
        $cases = [];
        $bad = $this->catalog;
        $bad['families'][0]['parameters'][0]['contract'] = 'everything';
        $cases[] = $bad;
        $bad = $this->catalog;
        $bad['families'][0]['operations'][2]['parameters'][1]['type'] = '$absent';
        $cases[] = $bad;
        $bad = $this->catalog;
        $bad['families'][0]['operations'][2]['requires'][0]['operation'] = 'default_construct';
        $cases[] = $bad;
        $bad = $this->catalog;
        $bad['families'][0]['operations'][2]['effects'][0]['receiver'] = 1;
        $cases[] = $bad;
        $bad = $this->catalog;
        $bad['families'][0]['lifecycle']['destroy'] = 'length';
        $cases[] = $bad;
        $bad = $this->catalog;
        $bad['families'][0]['operations'][2]['effects'][] = ['kind' => 'safe_element_input', 'receiver' => 0, 'input' => 0];
        $cases[] = $bad;
        foreach ($cases as $bad) {
            $this->reject(fn() => family\Catalog::parse($bad, 'simple_cpp'), 'malformed family contract');
        }
        $args = family\Requests::arguments($catalog, $catalog->families['sequence'], ['signed32']);
        $this->reject(fn() => family\Requests::resolve(new \type_model\provider_type_reference('foreign', 'signed32'),
            $catalog->families['sequence'], $args, 'instance'), 'equal native spelling cannot authorize foreign identity');
        $this->reject(fn() => family\Requests::resolve(new \type_model\parameter_type_reference($holder->key(), 0),
            $catalog->families['sequence'], $args, 'instance'), 'formal owner cannot be replaced by matching slot');
        $safe = $this->catalog;
        $safe['families'][0]['operations'][2]['effects'][] = ['kind' => 'safe_element_input', 'receiver' => 0, 'input' => 1];
        $effects = family\Catalog::parse($safe, 'simple_cpp')->families['sequence']->semantic->operations['append_copy']->effects;
        $this->check((count($effects) === 2) && ($effects[0]->kind !== $effects[1]->kind), 'overlap and invalidation are distinct facts');
        $renamed = $this->catalog;
        $renamed['families'][0]['id'] = 'renamed';
        $renamed['families'][0]['operations'][2]['id'] = 'push';
        $other = family\Catalog::parse($renamed, 'simple_cpp');
        $expanded = family\Requests::export($other->families['renamed'], family\Requests::arguments($other,
            $other->families['renamed'], ['signed64']), ['push'], 'proof', 'renamed_instance');
        $this->check($expanded['operations'][0]['parameters'][1]['type'] === 'signed64', 'renamed family and changed element use common binding');
        $collision = $this->catalog;
        $collision['types'][0]['id'] = 'instance';
        $this->check(family\Requests::instance_id(family\Catalog::parse($collision, 'simple_cpp')) !== 'instance',
            'generated type identity does not reserve a catalog name');
    }

    /** Rejected candidates never replace accepted bytes, even with valid but incompatible layouts. */
    private function candidate_failures(family\Preparation $preparation, string $output, string $pointer): void
    {
        $task = $preparation->select($this->request('sequence', ['signed32'], ['read_copy']));
        $result = family\Preparation::execute($task);
        try {
            native\Files::write($result->candidate->directory . '/request.json', '{}');
            $this->reject(fn() => (new family\Join([$task]))->join([$result]), 'wrong candidate coverage');
            $this->reject(fn() => $result->candidate->publish(), 'artifact changed after sealing');
        }
        finally {
            $result->candidate->release();
        }
        $this->check(native\Files::read($output . '/current.json') === $pointer, 'candidate rejection preserves publication');
        $this->check(glob($output . '/.preparing-*') === [], 'private candidate cleanup');
        $task = $preparation->select($this->request('sequence', ['signed32'], ['read_copy']));
        $result = family\Preparation::execute($task);
        native\Files::write($task->configuration, '{}');
        try {
            $this->reject(fn() => (new family\Join([$task]))->join([$result]), 'input edit after generation');
            $this->reject(fn() => $result->candidate->publish(), 'input edit before publication');
        }
        finally {
            $result->candidate->release();
        }
        unset($result);
        $task = $preparation->select($this->request('sequence', ['signed32'], ['read_copy']));
        native\Files::write($task->configuration, '{}');
        $this->reject(fn() => family\Preparation::execute($task), 'changed selected inputs');
        unset($task);

        $bad = $this->catalog;
        $bad['families'][0]['operations'][2]['cpp_name'] = 'scpp_provider::missing_implementation';
        $request = new family\specialization_request(family\Catalog::parse($bad, 'simple_cpp'), 'sequence', ['signed32'],
            ['append_copy'], 'runtime', $this->context);
        $this->reject(fn() => $preparation->run($request), 'incompatible native implementation');
        $this->check(native\Files::read($output . '/current.json') === $pointer, 'generation failure preserves publication');
        $this->execute($output . '/package', false, 'after-rejection');
    }

    private function request(string $name, array $arguments, array $operations): family\specialization_request
    {
        return new family\specialization_request(family\Catalog::parse($this->catalog, 'simple_cpp'),
            $name, $arguments, $operations, 'runtime', $this->context);
    }

    /** Execute only the accepted generated bridge; fixture code never calls the native container directly. */
    private function execute(string $package, bool $holder, string $label): void
    {
        $metadata = native\Files::json($package . '/metadata.json');
        $manifest = native\Files::json($package . '/manifest.json');
        $types = array_column($metadata['types'], null, 'id');
        $ops = array_column($metadata['operations'], 'symbol', 'id');
        $instance = $types['instance'];
        $source = "#include \"abi.hpp\"\n#include <cstdint>\nint main() {\n";
        $source .= 'alignas(' . $instance['alignment_bytes'] . ') unsigned char object[' . $instance['size_bytes'] . "];\n";
        if ($holder) {
            $source .= $ops['instance.initialize'] . "(object, 41, 9);\n";
            $source .= 'auto observed = ' . $ops['instance.difference'] . "(object);\n";
            $source .= $ops['instance.release'] . "(object);\nreturn observed == 32 ? 0 : 1;\n}\n";
        }
        else {
            $source .= $ops['instance.construct'] . "(object);\nstd::int32_t value = 7;\n";
            $source .= $ops['instance.append_copy'] . "(object, &value);\n";
            $source .= 'auto observed = ' . $ops['instance.length'] . "(object);\n";
            $source .= $ops['instance.destroy'] . "(object);\nreturn observed == 1 ? 0 : 1;\n}\n";
        }
        $file = $this->workspace . '/' . $label . '.cpp';
        native\Files::write($file, $source);
        foreach (['ordinary' => 'runtime.bc', 'full' => 'runtime.lto.bc', 'thin' => 'runtime.thin.bc'] as $mode => $module)
        {
            $binary = $this->workspace . '/' . $label . '-' . $mode;
            $flags = $mode === 'ordinary' ? [] : ['-flto=' . $mode, '--ld-path=' . native\Files::executable('/', 'ld.lld-18')];
            $this->toolchain->run([$manifest['link_driver']['executable'], ...$manifest['link_driver']['arguments'],
                '-std=c++23', '-O1', ...$flags, '-I' . $package, $file, $package . '/' . $module, '-o', $binary]);
            $this->toolchain->run([$binary]);
            $this->check(true, $label . ' ' . $mode . ' generated ABI execution');
        }
    }

    private function check(bool $value, string $message): void
    {
        if (!$value) {
            throw new \RuntimeException('Failed: ' . $message);
        }
        $this->checks++;
    }

    /** Fail if the selected malformed case reaches normal completion. */
    private function reject(callable $operation, string $message): void
    {
        try {
            $operation();
        }
        catch (\RuntimeException) {
            $this->checks++;
            return;
        }
        throw new \RuntimeException('Expected rejection: ' . $message);
    }
}

(new Family_Test($argv[1] ?? sys_get_temp_dir() . '/family-proof-' . bin2hex(random_bytes(8))))->run();
