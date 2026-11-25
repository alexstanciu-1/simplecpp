<?php
declare(strict_types=1);
require_once __DIR__ . '/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';
use Body_Test_Stages as Check;
use prepare_backend as backend;
use runtime_preparation\Files;

final class Source_Export_Test
{
    /** Project exact nominal provenance from accepted compiler outputs, excluding synthesized instance names. */
    public static function identities(\compile\Compile_Result $compiled, \compile\native_project $project): \resolve_types\Source_Identities
    {
        return new \resolve_types\Source_Identities($project, $compiled->types->types, $compiled->symbols->current,
            $compiled->types->instances, $compiled->backend->runtime?->base_catalog ?? $compiled->types->catalog,
            $compiled->backend->runtime);
    }

    /** Exercise the public selected boundary; no alternative compiler path or fixture-aware behavior. */
    public static function capture(\compile\Compile_Result $compiled, \compile\native_project $project, array $roots): array
    {
        return backend\Source_Export_Preparation::capture($project, self::identities($compiled, $project),
            $compiled->types->types, $roots, $compiled->backend->layouts, $compiled->backend->configuration);
    }

    /** Capture the observable program result independently of export contract inspection. */
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']], $pipes);
        return proc_close($process);
    }
    /** Build real managed source records once per isolated proof workspace. */
    public static function project(string $root): array
    {
        Files::directory($root . '/definitions');
        Files::directory($root . '/project');
        // Real managed native field: implementation and capabilities arrive through preparation metadata.
        Files::write($root . '/field.hpp', <<<'CPP'
        #pragma once
        #include <cstdint>
        namespace proof {
        inline std::int64_t live = 0, created = 0, released = 0, events = 0;
        inline std::int64_t note(std::int64_t value) { events += value; return 0; }
        inline std::int64_t notes() { return events; }
        inline std::int64_t balanced() { return live == 0 && created > 0 && created == released; }
        struct field {
            int *value;
            field() : value(new int(7)) { ++live; ++created; }
            field(const field& source) : value(new int(*source.value)) { ++live; ++created; }
            field& operator=(const field& source) { *value = *source.value; return *this; }
            ~field() { delete value; --live; ++released; }
        };
        }
        CPP);
        $operations = [];
        foreach (['default' => 'construct', 'copy' => 'copy_construct', 'assign' => 'copy_assign', 'destroy' => 'destroy'] as $id => $kind) {
            $operation = ['id' => 'field.' . $id, 'kind' => $kind, 'type' => 'field', 'error_policy' => 'terminate'];
            if ($kind === 'construct') {
                $operation['parameters'] = [];
            }
            $operations[] = $operation;
        }
        $operations[] = ['id' => 'balanced', 'kind' => 'free_function', 'cpp_name' => 'proof::balanced', 'header' => 'field.hpp',
            'parameters' => [], 'result_type' => 'native_int', 'error_policy' => 'terminate',
            'expose_as' => ['name' => 'balanced', 'namespace' => '']];
        foreach (['note' => ['native_int'], 'notes' => []] as $name => $parameters) {
            $operations[] = ['id' => $name, 'kind' => 'free_function', 'cpp_name' => 'proof::' . $name, 'header' => 'field.hpp',
                'parameters' => $parameters, 'result_type' => 'native_int', 'error_policy' => 'terminate',
                'expose_as' => ['name' => $name, 'namespace' => '']];
        }
        Files::write_json($root . '/definitions/field.json', ['schema_version' => 1, 'types' => [
            ['id' => 'native_int', 'kind' => 'integer', 'cpp_name' => 'std::int64_t', 'header' => 'cstdint',
                'language_type' => ['name' => 'int', 'namespace' => '']],
            ['id' => 'field', 'kind' => 'runtime_value', 'cpp_name' => 'proof::field', 'header' => 'field.hpp',
                'storage' => 'inline', 'struct_field' => true, 'language_type' => ['name' => 'managed', 'namespace' => ''],
                'lifecycle' => ['default_construct' => 'field.default', 'copy_construct' => 'field.copy',
                    'copy_assign' => 'field.assign', 'destroy' => 'field.destroy']],
        ], 'operations' => $operations]);
        $preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
        $config = Files::json($preparation . '/config.json');
        $config['definitions_directory'] = $root . '/definitions';
        $config['output_directory'] = $root . '/runtime';
        $config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($preparation, $path)),
            $config['include_directories']), $root];
        Files::write_json($root . '/config.json', $config);
        (new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
        Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
        Files::write($root . '/project/types.phs', <<<'PHS'
        struct first { public int32 $number; public managed $resource; }
        struct second { public int32 $number; public managed $resource; }
        struct nested { public first $child; public int32 $tail; }
        struct plain { public int32 $number; }
        struct custom { public int32 $number; public function __destruct(): void {} }
        struct enclosing_custom { public custom $child; }
        template<typename A, typename B, int N>
        struct marker { public int32 $value; }
        PHS);
        Files::write($root . '/project/main.phs', 'return exercise();');
        $body = <<<'PHS'
        function exercise(): int {
            $a first = new first();
            $b first = $a;
            $b = $a;
            $c nested = new nested();
            $d marker<int32, uint8, 3> = new marker<int32, uint8, 3>();
            $e marker<uint8, int32, 3> = new marker<uint8, int32, 3>();
            $f marker<int32, uint8, 4> = new marker<int32, uint8, 4>();
            $g marker<marker<int32, uint8, 3>, first, 5> = new marker<marker<int32, uint8, 3>, first, 5>();
            return 7;
        }
        PHS;
        Files::write($root . '/project/body.phs', $body);
        $session = new \compile\Compiler_Session(runtime_package_path: $root . '/runtime');
        $first = $session->compile($root . '/project/project.json', $root . '/program');
        Check::check(Source_Export_Test::run($root . '/program') === 7, 'Source managed lifecycle executes through the existing native pipeline');
        $project = new \compile\native_project('proof/project_X_key', $root . '/project', $root . '/native-output');
        return [$session, $first, $project, $body];
    }
}
