<?php
declare(strict_types=1);
namespace load_runtime;

/** Compiler-side consumption of accepted source exports; producer preparation remains separate. */
final class Project_Import {
    public static function source_type(\scpp\Json_View $row, \prepare_backend\Source_Type_Export $source_export, Runtime_Storage $storage): \type_model\Named_Definition {
        $layout = $source_export->task->layout;
        if (($row->member('source_payload')->text() !== $source_export->task->identity->key())
            || ($storage->size_bytes !== $layout->size) || ($storage->alignment_bytes !== $layout->alignment)
            || ($row->member('kind')->text() !== 'runtime_value')) { throw new \RuntimeException('Source payload does not match its accepted compiler definition'); }
        $lifecycle = $row->member('lifecycle');
        if (($lifecycle->kind() !== 'array') && ($lifecycle->kind() !== 'object')) { throw new \RuntimeException('Source payload does not match its accepted compiler definition'); }
        if ($lifecycle->size() !== 0) { throw new \RuntimeException('Source payload does not match its accepted compiler definition'); }
        $forbidden /** vector<string> */ = ['language_type','native_import','resource','storage_family','struct_field'];
        foreach ($forbidden as $name) {
            if ($row->has($name)) {
                if ($row->member($name)->kind() !== 'null') { throw new \RuntimeException('Source payload does not match its accepted compiler definition'); }
            }
        }
        return $layout->definition;
    }
}
