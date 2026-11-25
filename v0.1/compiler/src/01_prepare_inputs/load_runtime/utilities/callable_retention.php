<?php
declare(strict_types=1);
namespace load_runtime;

/** Reuse exact unchanged contracts after import validation, retaining the new package's order/coverage. */
final class Callable_Retention {
    public static function retain(array $current /** vector<\type_model\Runtime_Callable> */,
        array $previous /** vector<\type_model\Runtime_Callable> */): array /** vector<\type_model\Runtime_Callable> */ {
        $old /** hash<\type_model\Runtime_Callable> */ = [];
        foreach ($previous as $callable) { $old[$callable->id] = $callable; }
        $result /** vector<\type_model\Runtime_Callable> */ = [];
        foreach ($current as $callable) {
            $selected = $callable;
            if (isset($old[$callable->id])) {
                if (\type_model\Callable_Contracts::same($old[$callable->id],$callable)) { $selected = $old[$callable->id]; }
            }
            $result[] = $selected;
        }
        return $result;
    }
}
