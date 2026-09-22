<?php
declare(strict_types=1);
require_once __DIR__ . '/source_export_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/families/compiler_bridge.php';

use runtime_preparation\Files;
use runtime_preparation\families as native;

final class Source_Family_Test
{
    /** Prepare the real metadata-driven family and managed field used by source/native execution proofs. */
    public static function setup(string $root): array
    {
        [, , $scope] = Source_Export_Test::project($root);
        $base = dirname(__DIR__, 2) . '/src-runtime-preparation';
        $config = Files::json($root . '/config.json');
        $language = Files::json(dirname(__DIR__, 2) . '/language/named_types.json');
        $language['types'][1]['struct_field'] = true;
        $language['types'][] = ['name' => 'size_count', 'namespace' => '', 'kind' => 'integer', 'bit_width' => 64, 'signed' => false,
            'lifetime' => ['copy' => 'value', 'cleanup' => 'none'], 'integer_family' => 'simple_cpp.integer', 'comparison' => 'ordered'];
        Files::write_json($root . '/language.json', $language);
        $data = Files::json($base . '/tests/families/catalog.json');
        $data['families'] = [$data['families'][0]];
        $data['families'][0]['language_type'] = ['name' => 'vector', 'namespace' => ''];
        $data['families'][0]['parameters'][0]['source_profiles'] = [\runtime_preparation\project\source_type::PROFILE];
        foreach ($data['types'] as &$type) {
            $type['language_type'] = ['name' => match ($type['id']) {
                'signed32' => 'int32', 'signed64' => 'int', 'byte' => 'uint8', 'size' => 'size_count', 'nothing' => 'void',
            }, 'namespace' => ''];
        }
        unset($type);
        foreach ($data['families'][0]['operations'] as &$operation)
        {
            $name = ['append_copy' => 'append', 'length' => 'length', 'read_copy' => 'at'][$operation['id']] ?? null;
            if ($name !== null) {
                $operation['expose_as'] = ['name' => $name, 'namespace' => ''];
            }
            foreach ($operation['parameters'] ?? [] as $i => $parameter) {
                if (is_array($parameter) && ($parameter['type'] === '$element')) {
                    $operation['parameters'][$i]['source_payload'] = 'copy_in';
                }
            }
            if (($operation['result_type'] ?? null) === '$element') {
                $operation['source_payload_result'] = 'copy_out';
            }
        }
        unset($operation);
        foreach (['copy_construct', 'copy_assign'] as $role) {
            $data['families'][0]['lifecycle'][$role] = $role;
            $data['families'][0]['operations'][] = ['id' => $role, 'kind' => $role, 'type' => '$self', 'error_policy' => 'terminate'];
        }
        $catalog = native\Catalog::parse($data, 'source_arguments');
        $declarations = \load_runtime\Family_Adapter::expose(array_map(static fn($family) => $family->semantic, $catalog->families), native\Catalog::language_bindings($catalog));
        $provider = new native\compiler_provider($catalog, $root . '/shared-families', 'shared-runtime',
            array_intersect_key($config, array_flip(['clang', 'target', 'standard', 'include_directories'])));
        $session = new \compile\Compiler_Session(runtime_package_path: $root . '/runtime', type_catalog_path: $root . '/language.json',
            family_declarations: $declarations, family_preparer: new native\Compiler_Bridge([$provider]), native_project: $scope);
        return [$session, $scope, $config];
    }
}
