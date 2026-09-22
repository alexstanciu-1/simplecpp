<?php
declare(strict_types=1);

require_once __DIR__ . '/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

/** Shared native observation for storage mechanics and source-written container proofs. */
final class Managed_Storage_Fixture
{
    /** Capture native lifecycle observations and fatal guards without depending on compiler output text. */
    public static function run(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start managed storage proof');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }
    /** Prepare metadata-defined storage and heap-owning elements; native observations check both ownership layers. */
    public static function prepare(string $root): void
    {
        $preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
        Files::directory($root . '/definitions');
        Files::directory($root . '/project');
        $storage = Files::json($preparation . '/definitions/element_storage.json');
        $storage['types'][0]['language_type']['name'] = 'cells';
        foreach ($storage['types'][0]['storage_family']['operations'] as $role => $_) {
            $storage['types'][0]['storage_family']['operations'][$role] = 'cells_' . $role;
        }
        $storage['types'][] = Files::json($preparation . '/definitions/scalars.json')['types'][0];
        $storage['types'][] = ['id' => 'void', 'cpp_name' => 'void', 'header' => 'cstddef', 'kind' => 'void',
            'language_type' => ['name' => 'void', 'namespace' => '']];
        foreach ($storage['operations'] as &$operation) {
            if (in_array($operation['id'], ['element_storage.allocate', 'element_storage.release'], true)) {
                $operation['cpp_name'] = 'proof::' . substr($operation['id'], strlen('element_storage.'));
                $operation['header'] = 'observer.hpp';
            }
        }
        unset($operation);
        Files::write_json($root . '/definitions/storage.json', $storage);
        Files::write($root . '/observer.hpp', <<<'CPP'
        #pragma once
        #include <scpp_provider/element_storage.hpp>
        #include <cstdint>
        #include <cstdio>
        #include <cstdlib>
        #include <unordered_set>
        namespace proof {
        inline std::unordered_set<const void*> live;
        inline int allocations = 0, releases = 0;
        inline std::unordered_set<void*> buffers;
        inline std::int64_t acquired = 0, freed = 0;
        inline void allocate(scpp_provider::element_storage& owner, std::int64_t n, std::int64_t size, std::int64_t alignment) {
            scpp_provider::storage_allocate(owner, n, size, alignment);
            if (!buffers.insert(owner.memory.address).second) std::abort();
            ++acquired;
        }
        inline void release(scpp_provider::element_storage& owner) {
            if (owner.memory.address) {
                if (buffers.erase(owner.memory.address) != 1) std::abort();
                ++freed;
            }
            scpp_provider::storage_release(owner);
        }
        inline std::int64_t buffer_count() { return acquired; }
        struct alignas(64) observed {
            const observed* identity;
            std::int64_t* value;
            explicit observed(std::int64_t n = 0) : identity(this), value(new std::int64_t(n)) {
                ++allocations;
                if (!live.insert(this).second) std::abort();
                std::printf("C:%lld\n", (long long)n); std::fflush(stdout);
            }
            observed(const observed& source) : identity(this), value(new std::int64_t(source.read())) {
                ++allocations;
                if (!live.insert(this).second) std::abort();
                std::printf("K:%lld\n", (long long)*value); std::fflush(stdout);
            }
            observed& operator=(const observed& source) {
                const auto previous = read();
                const auto next = source.read();
                std::printf("%c:%lld:%lld\n", this == &source ? 'S' : 'A', (long long)previous, (long long)next); std::fflush(stdout);
                // Allocate before releasing, including self-assignment: both objects must still be live.
                auto replacement = new std::int64_t(next);
                ++allocations; delete value; ++releases; value = replacement;
                return *this;
            }
            ~observed() {
                std::printf("D:%lld\n", (long long)read()); std::fflush(stdout);
                live.erase(this); delete value; ++releases;
            }
            std::int64_t read() const {
                if (identity != this || !live.contains(this) || reinterpret_cast<std::uintptr_t>(this) % alignof(observed)) std::abort();
                return *value;
            }
        };
        inline std::int64_t finished() {
            if (!live.empty() || allocations != releases || !buffers.empty() || acquired != freed) std::abort();
            return 0;
        }
        }
        CPP);
        $definition = ['schema_version' => 1, 'types' => [
            ['id' => 'observed', 'kind' => 'runtime_value', 'cpp_name' => 'proof::observed', 'header' => 'observer.hpp',
                'storage' => 'inline', 'struct_field' => true, 'language_type' => ['name' => 'observed', 'namespace' => ''],
                'lifecycle' => ['default_construct' => 'observed.default', 'copy_construct' => 'observed.copy',
                    'copy_assign' => 'observed.assign', 'destroy' => 'observed.destroy']],
        ], 'operations' => []];
        foreach (['default' => 'construct', 'copy' => 'copy_construct', 'assign' => 'copy_assign', 'destroy' => 'destroy'] as $id => $kind) {
            $operation = ['id' => 'observed.' . $id, 'kind' => $kind, 'type' => 'observed', 'error_policy' => 'terminate'];
            if ($kind === 'construct') {
                $operation['parameters'] = [];
            }
            $definition['operations'][] = $operation;
        }
        $definition['operations'][] = ['id' => 'observed.make', 'kind' => 'construct', 'type' => 'observed',
            'parameters' => ['native_int'], 'error_policy' => 'terminate', 'expose_as' => ['name' => 'make_observed', 'namespace' => '']];
        $definition['operations'][] = ['id' => 'observed.read', 'kind' => 'const_method', 'type' => 'observed',
            'member' => 'read', 'result_type' => 'native_int', 'borrow_scope' => 'call', 'error_policy' => 'terminate',
            'expose_as' => ['name' => 'read_observed', 'namespace' => '']];
        $definition['operations'][] = ['id' => 'finished', 'kind' => 'free_function', 'cpp_name' => 'proof::finished',
            'header' => 'observer.hpp', 'parameters' => [], 'result_type' => 'native_int', 'error_policy' => 'terminate',
            'expose_as' => ['name' => 'finished', 'namespace' => '']];
        $definition['operations'][] = ['id' => 'buffer_count', 'kind' => 'free_function', 'cpp_name' => 'proof::buffer_count',
            'header' => 'observer.hpp', 'parameters' => [], 'result_type' => 'native_int', 'error_policy' => 'terminate',
            'expose_as' => ['name' => 'buffer_count', 'namespace' => '']];
        // A second nominal runtime type has copy/cleanup but exposes no default-construction permission.
        $manual = $definition['types'][0];
        $manual['id'] = 'manual';
        $manual['language_type']['name'] = 'manual';
        unset($manual['lifecycle']['default_construct']);
        foreach ($manual['lifecycle'] as &$operation) {
            $operation = str_replace('observed.', 'manual.', $operation);
        }
        unset($operation);
        $definition['types'][] = $manual;
        foreach ($definition['operations'] as $operation)
        {
            if ((($operation['type'] ?? null) === 'observed') && ($operation['id'] !== 'observed.default'))
            {
                $operation['type'] = 'manual';
                $operation['id'] = str_replace('observed.', 'manual.', $operation['id']);
                if (isset($operation['expose_as'])) {
                    $operation['expose_as']['name'] = str_replace('observed', 'manual', $operation['expose_as']['name']);
                }
                $definition['operations'][] = $operation;
            }
        }
        Files::write_json($root . '/definitions/observed.json', $definition);
        $config = Files::json($preparation . '/config.json');
        $config['definitions_directory'] = $root . '/definitions';
        $config['output_directory'] = $root . '/runtime';
        $config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($preparation, $path)),
            $config['include_directories']), $root];
        Files::write_json($root . '/config.json', $config);
        (new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
    }
}
