<?php
declare(strict_types=1);
namespace prepare_backend;

/** Select work from an immutable captured graph; tool execution belongs to preparation. */
final class Layout_Selection {
    public static function select(Layout_Input $input, Backend_Configuration $configuration,
        array $previous /** hash<Storage_Layout,int> */, bool $full, array $command /** vector<string> */,
        string $launcher, array $native_command /** vector<string> */): array /** vector<Layout_Task> */ {
        $tasks /** vector<Layout_Task> */ = [];
        foreach ($input->roots as $id) {
            if (!$full) {
                if (isset($previous[$id])) {
                    if (Layout_Capture::current($previous[$id],$input,$id,$configuration)) { continue; }
                }
            }
            $fields = $input->fields_for($id); $field_types /** vector<string> */ = [];
            foreach ($fields as $field) { $field_types[] = LLVM_Storage::compound($input,$field->type_id); }
            $tasks[] = new Layout_Task($id,$input->definition_for_type($id),$fields,$field_types,
                $configuration,$command,$launcher,$input,$native_command,LLVM_Storage::requires_native($input,$id));
        }
        return $tasks;
    }
}
