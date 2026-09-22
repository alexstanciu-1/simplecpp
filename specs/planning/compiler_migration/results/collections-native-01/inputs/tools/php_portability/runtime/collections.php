<?php
declare(strict_types=1);

namespace scpp\portability_runtime {

/** Host approximation: the caller selects output policy; array shape never selects it. */
function collect(array $input, \Closure $callback, bool $keyed, bool $filter): array {
    if (!$keyed && !array_is_list($input)) {
        throw new \InvalidArgumentException('Sequence operations require a dense list');
    }
    $output = [];
    foreach ($input as $key => $value) {
        $mapped = $callback($value);
        if ($filter) {
            if (!is_bool($mapped)) {
                throw new \UnexpectedValueException('Collection predicate must return bool');
            }
            if (!$mapped) { continue; }
            $mapped = $value;
        }
        if ($keyed) { $output[$key] = $mapped; }
        else { $output[] = $mapped; }
    }
    return $output;
}

}

namespace scpp {

// Explicit sequence/keyed policy; native adapters require the #231 candidate.
// Callbacks and reachable input/captured objects must remain read-only.
function sequence_map(array $input, \Closure $callback): array {
    return \scpp\portability_runtime\collect($input, $callback, false, false);
}

function sequence_filter(array $input, \Closure $predicate): array {
    return \scpp\portability_runtime\collect($input, $predicate, false, true);
}

function keyed_map(array $input, \Closure $callback): array {
    return \scpp\portability_runtime\collect($input, $callback, true, false);
}

function keyed_filter(array $input, \Closure $predicate): array {
    return \scpp\portability_runtime\collect($input, $predicate, true, true);
}

}
