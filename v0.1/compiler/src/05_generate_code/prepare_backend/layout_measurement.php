<?php
declare(strict_types=1);
namespace prepare_backend;

/** Validate captured tool output into a private result; batch acceptance belongs to the join. */
final class Layout_Measurement {
    public static function read(Layout_Task $task, string $output, string $primitive_output): Layout_Result {
        if ($task->aligned !== LLVM_Storage::requires_native($task->input,$task->type_id)) {
            throw new \LogicException('Selected layout policy disagrees with field storage');
        }
        $names /** vector<string> */ = ['size','alignment'];
        foreach ($task->fields as $index => $field) { $names[] = 'field_' . $index; }
        $primitive_names /** vector<string> */ = [];
        if ($task->aligned) {
            $witness = Native_Layout::source($task->input,$task->type_id);
            foreach ($witness->primitives as $id => $type) {
                $primitive_names[] = 'primitive_size_' . $id; $primitive_names[] = 'primitive_alignment_' . $id;
            }
        }
        foreach ($primitive_names as $name) { $names[] = $name; }
        $facts = Layout_Facts::read($output,$task->configuration,$names);
        if (q_count($primitive_names) > 0) {
            $llvm = Layout_Facts::read($primitive_output,$task->configuration,$primitive_names);
            foreach ($primitive_names as $name) {
                if ($facts[$name] !== $llvm[$name]) { throw new \RuntimeException('Native layout primitive disagrees with LLVM: ' . $name); }
            }
        }
        $offsets /** vector<int> */ = []; foreach ($task->fields as $index => $field) { $offsets[] = $facts['field_' . $index]; }
        $spelling = LLVM_Storage::compound($task->input,$task->type_id);
        if ($task->aligned) { $spelling = '[' . $facts['size'] . ' x i8]'; }
        $layout = new Storage_Layout($task->definition,$task->configuration,$task->fields,$spelling,$facts['size'],$facts['alignment'],
            $offsets,$task->input->lineage,$task->input->dependencies[$task->type_id]);
        return new Layout_Result($task,$layout);
    }
}
