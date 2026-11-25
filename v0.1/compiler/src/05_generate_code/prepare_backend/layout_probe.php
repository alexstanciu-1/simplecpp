<?php
declare(strict_types=1);
namespace prepare_backend;

/** LLVM constants for target folding; no target machine code is executed. */
final class Layout_Probe {
    private static function header(Backend_Configuration $configuration): string {
        return 'target triple = ' . LLVM_Text::quote($configuration->target_triple) . "\n"
            . 'target datalayout = ' . LLVM_Text::quote($configuration->data_layout) . "\n";
    }
    private static function fact(string $name, string $query): string {
        return '@' . $name . ' = constant i64 ptrtoint (ptr ' . $query . " to i64)\n";
    }
    public static function source(Layout_Task $task): string {
        $type = LLVM_Storage::compound($task->input,$task->type_id); $text = Layout_Probe::header($task->configuration);
        $text = $text . Layout_Probe::fact('size','getelementptr (' . $type . ', ptr null, i32 1)');
        $text = $text . Layout_Probe::fact('alignment','getelementptr ({ i8, ' . $type . ' }, ptr null, i32 0, i32 1)');
        foreach ($task->fields as $index => $field) {
            $text = $text . Layout_Probe::fact('field_' . $index,'getelementptr (' . $type . ', ptr null, i32 0, i32 ' . $index . ')');
        }
        return $text;
    }
    public static function primitives(Backend_Configuration $configuration, array $types /** hash<string,int> */): string {
        $text = Layout_Probe::header($configuration);
        foreach ($types as $id => $type) {
            $text = $text . Layout_Probe::fact('primitive_size_' . $id,'getelementptr (' . $type . ', ptr null, i32 1)');
            $text = $text . Layout_Probe::fact('primitive_alignment_' . $id,'getelementptr ({ i8, ' . $type . ' }, ptr null, i32 0, i32 1)');
        }
        return $text;
    }
}
