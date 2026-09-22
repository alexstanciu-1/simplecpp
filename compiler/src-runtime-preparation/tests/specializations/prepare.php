<?php
declare(strict_types=1);

namespace runtime_preparation;

require_once dirname(__DIR__, 2) . '/bootstrap.php';

/** An isolated fixture driver: source descriptions stand in for future resolved compiler exports. */
final class Specialization_Driver
{
    /** Materialize fixed request inputs in a caller-owned workspace, prepare, and accept the package. */
    public static function run(string $workspace, string $request_path, string $catalog_path): void
    {
        $base = dirname(__DIR__, 2);
        $config = Files::json($base . '/config.json');
        $request = Files::json($request_path);
        $catalog = Files::json($catalog_path);
        $export = Specialization_Request::export($request, $catalog, $config['provider']);
        $config['provider'] = $export['provider'];
        Files::directory($workspace . '/inputs/definitions');
        Files::write($workspace . '/inputs/source_types.hpp', $export['header']);
        Files::write_json($workspace . '/inputs/definitions/request.json', $export['definitions']);
        $config['definitions_directory'] = $workspace . '/inputs/definitions';
        $config['output_directory'] = $workspace . '/output';
        $config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($base, $path)),
            $config['include_directories']), $workspace . '/inputs'];
        Files::write_json($workspace . '/config.json', $config);

        $definitions = new Definitions($config['definitions_directory']);
        $prepared = (new Runtime_Preparation())->run($workspace . '/config.json');
        $accepted = new Prepared_Request($config['output_directory'], $definitions, $config['provider'], $prepared['input_key']);
        Files::write_json($workspace . '/accepted.json', ['preparation' => $prepared, 'types' => $accepted->types,
            'operations' => $accepted->operations, 'manifest' => $accepted->manifest,
            'package' => $accepted->package, 'bindings' => $export['bindings'], 'request' => $request, 'config' => $config]);
        fwrite(STDOUT, json_encode($prepared, JSON_THROW_ON_ERROR) . "\n");
    }
}

Specialization_Driver::run($argv[1], $argv[2] ?? __DIR__ . '/request.json', $argv[3] ?? __DIR__ . '/catalog.json');
