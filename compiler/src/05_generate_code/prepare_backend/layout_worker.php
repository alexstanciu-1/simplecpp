<?php
declare(strict_types=1);
namespace prepare_backend;

/** Execute one selected measurement; result publication remains the layout join's responsibility. */
final class Layout_Worker {
    public static function prepare(Layout_Task $task, int $timeout_ms): Layout_Result {
        if ($task->aligned !== LLVM_Storage::requires_native($task->input,$task->type_id)) { throw new \LogicException('Selected layout policy disagrees with field storage'); }
        $output = ''; $primitive_output = '';
        if ($task->aligned) {
            if (q_count($task->native_command) === 0) { throw new \LogicException('Aligned layout requires its selected native tool command'); }
            $witness = Native_Layout::source($task->input,$task->type_id);
            $output = Tool_Run::run($task->native_command,$witness->source,$timeout_ms);
            $header_only /** vector<string> */ = [];
            $verified = Layout_Facts::read($output,$task->configuration,$header_only);
            if (q_count($witness->primitives) > 0) {
                $source = Layout_Probe::primitives($task->configuration,$witness->primitives);
                $primitive_output = Tool_Run::run($task->command,$source,$timeout_ms);
            }
        } else { $output = Tool_Run::run($task->command,Layout_Probe::source($task),$timeout_ms); }
        return Layout_Measurement::read($task,$output,$primitive_output);
    }
}
