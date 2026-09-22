<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';

use check_bodies\value_kind;
use check_bodies\conversion_kind;
use check_bodies\Conversion_Resolver;
use lower\Lowerer;
use lower\Lowered_Set;
use lower\instruction_kind;
use emit_llvm\LLVM_Emitter;
use analyze_lifetimes\lifetime_end;

class Integer_Conversions_Test extends Body_Test_Stages
{
    /** Execute a conversion probe with a bounded wait and release its process on every exit. */
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        self::check(is_resource($process), 'Start conversion executable');
        try
        {
            $deadline = hrtime(true) + 2_000_000_000;
            do
            {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    return $status['exitcode'];
                }
                self::check(hrtime(true) < $deadline, 'Conversion executable timed out');
                usleep(10000);
            }
            while (true);
        }
        finally {
            if (proc_get_status($process)['running']) {
                proc_terminate($process, 9);
            }
            proc_close($process);
        }
    }

    /** Supply full-width inputs through a test-only LLVM caller and verify the actual emitted widening result. */
    public static function probe(\compile\Compile_Result $result, int $symbol_id, string $input, string $expected): void
    {
        $toolchain = new \prepare_backend\LLVM_Toolchain();
        $configuration = $toolchain->configuration();
        self::check($configuration == $result->backend->configuration, 'Native conversion probe uses the compiled target');
        $function = $result->llvm->function_for($symbol_id);
        $binding = $function->body->binding;
        $parameter = \prepare_backend\LLVM_Types::storage($binding->parameters[0]->definition->representation);
        $return = \prepare_backend\LLVM_Types::storage($binding->return_definition->representation);
        $native = 'i' . $result->llvm->entry->native_bits;
        $ir = 'target triple = ' . \prepare_backend\LLVM_Types::quote($configuration->target_triple) . "\n"
            . 'target datalayout = ' . \prepare_backend\LLVM_Types::quote($configuration->data_layout) . "\n"
            . $function->ir . 'define ' . $result->llvm->entry->calling_convention . ' ' . $native . ' @main() { entry:' . "\n"
            . ' %value = call ' . $binding->calling_convention . ' ' . $return . ' @' . \prepare_backend\LLVM_Types::quote($binding->link_name)
            . '(' . $parameter . ' ' . $input . ")\n"
            . ' %ok = icmp eq ' . $return . ' %value, ' . $expected . "\n"
            . ' %status = select i1 %ok, ' . $native . ' 0, ' . $native . " 1\n ret " . $native . " %status\n}\n"
            . 'attributes #0 = { "target-cpu"=' . \prepare_backend\LLVM_Types::quote($configuration->cpu)
            . ' "target-features"=' . \prepare_backend\LLVM_Types::quote($configuration->features) . " }\n";
        $object = getcwd() . '/conversion-probe.o';
        $output = getcwd() . '/conversion-probe';
        $toolchain->compile_object($ir, $object, $configuration);
        $toolchain->link_objects([$object], $output, $configuration);
        self::check(self::run($output) === 0, 'Actual emitted conversion preserves the full mathematical value');
    }

    public static function id(\compile\Compile_Result $result, string $name): int
    {
        return $result->symbols->current->find_symbol($name, '', \collect_symbols\symbol_kind::function_symbol);
    }
}
use Integer_Conversions_Test as Check;

$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$main = $root . '/src/main.phs';
$answer = $root . '/src/answer.phs';
$value = $root . '/src/nested/value.phs';
$output = getcwd() . '/conversions';
$catalog_path = getcwd() . '/types.json';
$catalog = json_decode(file_get_contents(dirname(__DIR__, 2) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);

// Existing provider configuration supplies narrow literals; shipped default stays int64.
$catalog['literal_types']['integer']['name'] = 'int32';
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
$main_source = '$a int = narrow(); $a = narrow(); sink(narrow()); return choose($a, accept(narrow()));';
$answer_source = 'function choose($first int, $second int): int { return $second; }
function accept($v int): int { $v = narrow(); return widen(narrow()); }
function widen($v int32): int { return $v; }
function sink($v int): void {}';
$value_source = 'function narrow(): int32 { return 42; }';
Check::edit($main, $main_source);
Check::edit($answer, $answer_source);
Check::edit($value, $value_source);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$first = $session->compile($manifest, $output);
Check::check(($first->completed) && (Check::run($output) === 42), 'Real source-to-native widening across initialization, assignment, nested arguments and calls');
$entry_id = $first->types->entry->symbol->symbol_id;
$narrow_id = Check::id($first, 'narrow');
$wide_id = Check::id($first, 'widen');
$accept_id = Check::id($first, 'accept');
$body = $first->bodies->for_symbol($entry_id);
$plan = $first->lowered->for_symbol($entry_id);
$conversion_values = [];
foreach ($body->values as $i => $v)
{
    if ($v->kind === value_kind::conversion) {
        $conversion_values[$i + 1] = $v;
        $input = $body->values[$v->payload->input_value_id - 1];
        Check::check(($input->type_id !== $v->type_id) && ($v->payload->operation === conversion_kind::integer_widen)
            && ($v->payload->input_value_id < ($i + 1)), 'Conversion retains its earlier source value and produces a new destination type');
    }
}
Check::check((count($conversion_values) === 4) && (count(array_filter($plan->instructions, static fn($i) => $i->kind === instruction_kind::convert)) === 4),
    'Four actual conversions, no extra identity nodes or repeated conversion at the consuming boundary');
Check::probe($first, $wide_id, '-2147483648', '-2147483648');
$ir = $first->llvm->function_for($entry_id)->ir;
Check::check((substr_count($ir, 'sext i32') === 4) && str_contains($first->llvm->function_for($wide_id)->ir, 'sext i32')
    && str_contains($first->llvm->function_for($accept_id)->ir, 'sext i32'), 'Return and parameter assignment use the same widening instruction');
$lifetime = $first->lifetimes->for_symbol($entry_id);
$conversion_ends = array_values(array_filter($lifetime->lifetimes, static fn($l) => $l->end === lifetime_end::conversion_input));
Check::check(count($conversion_ends) === 4, 'Every conversion consumes one scalar temporary');
foreach ($conversion_ends as $end) {
    Check::check($conversion_values[$end->consumer_id]->payload->input_value_id === $end->value_id,
        'Conversion consumption identifies the produced value, not a call or statement ID');
}
$kinds = array_column($plan->instructions, 'kind');
Check::check(array_slice($kinds, 0, 3) === [instruction_kind::call, instruction_kind::convert, instruction_kind::store],
    'Call result is converted before its local write');
$exports = json_decode($first->to_json(), true, 512, JSON_THROW_ON_ERROR);
$row = array_values(array_filter($exports['lowered'], static fn($b) => $b['callable_id'] === $entry_id))[0];
Check::check(($row['instructions'][1]['payload']['operation'] === 'sext')
    && ($row['instructions'][1]['payload']['input_value_id'] === 1), 'Debug export identifies actual conversion operation and input');

// No source spelling dispatch: compatible family and lossless shape relation own the rule.
$store = clone $first->types->types;
$int = $store->find_type('int');
$small = $store->find_type('int32');
$uint = \resolve_types\Type_Cache::materialize($store, $first->types->catalog->find_type('uint32', ''));
$context = new \resolve_types\Type_Resolution($store, $first->types->catalog, $first->types->entry, $first->types->signatures(), [], $first->resolutions);
$purpose = \type_model\conversion_purpose::implicit_boundary;
Check::check((Conversion_Resolver::resolve($context, new \check_bodies\conversion_request($small, $int, $purpose))?->primitive === conversion_kind::integer_widen)
    && (Conversion_Resolver::resolve($context, new \check_bodies\conversion_request($int, $small, $purpose)) === null)
    && (Conversion_Resolver::resolve($context, new \check_bodies\conversion_request($small, $uint, $purpose)) === null)
    && (Conversion_Resolver::resolve($context, new \check_bodies\conversion_request($small, $small, $purpose))?->form === \check_bodies\conversion_form::identity),
    'Implicit requests share widening, identity and loss-prevention rules');
foreach ([\type_model\conversion_purpose::explicit_cast, \type_model\conversion_purpose::condition, \type_model\conversion_purpose::text] as $other) {
    Check::check(Conversion_Resolver::resolve($context, new \check_bodies\conversion_request($small, $int, $other)) === null,
        'Implicit family permission does not invent explicit, truthiness or text conversions');
}
$definition = $store->definition_for_type($small);
foreach ([null, 'unrelated.integer'] as $family)
{
    $replacement = new \type_model\named_type_definition('Other', 'fixture', $definition->representation,
        $definition->lifetime, true, $family);
    $id = \resolve_types\Type_Cache::materialize($store, $replacement);
    $context = new \resolve_types\Type_Resolution($store, $first->types->catalog, $first->types->entry, $first->types->signatures(), [], $first->resolutions);
    Check::check(Conversion_Resolver::resolve($context, new \check_bodies\conversion_request($id, $int, $purpose)) === null,
        'Representation alone or another integer family grants no conversion');
    $store = clone $first->types->types;
}

// Fixed workers and same-path full selection preserve inputs and output order.
$before = serialize($first);
$tasks = \Step_Test::select(Lowerer::class, $first->lifetimes, $first->backend, new Lowered_Set(), true);
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($tasks));
$joined = (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->lowered->to_json()) && (serialize($first) === $before), 'Conversion lowering workers preserve fixed snapshots and deterministic joins');
$warm = $session->compile($manifest, $output);
Check::check(($warm->native === $first->native) && ($warm->llvm === $first->llvm), 'Warm conversion program reuses accepted native work');
Check::edit($value, str_replace('42', '43', $value_source));
$edited = $session->compile($manifest, $output);
Check::check((!$edited->inputs->context->full_rebuild) && (Check::run($output) === 43)
    && ($edited->lowered->for_symbol($entry_id) === $plan)
    && ($edited->native->object_for($plan->source_file_id()) === $first->native->object_for($plan->source_file_id())),
    'Callee body increment preserves caller conversion plans and objects');
$observed = $session->observed;
$published = $session->published;
$key = hash_file('sha256', $output);
Check::edit($main, '$x int = 42; $small int32 = $x; return $x;');
Check::rejects(static fn() => $session->compile($manifest, $output), 'Unsupported implicit initialization conversion from int to int32');
Check::check(($session->observed === $observed) && ($session->published === $published) && (hash_file('sha256', $output) === $key),
    'Unsupported narrowing preserves accepted snapshots and executable');
Check::edit($main, $main_source);
$repair = $session->compile($manifest, $output);
$fresh = (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($manifest, getcwd() . '/fresh-conversions');
Check::check((Check::run($output) === 43) && ($fresh->llvm->ir_by_file() === $repair->llvm->ir_by_file())
    && (serialize($first) === $before), 'Repair and fresh compilation agree without changing retained values');

// Policy belongs to shared type definitions; an edit must not reuse stale checked conversion nodes.
$without_family = $catalog;
foreach ($without_family['types'] as &$t) {
    if ($t['name'] === 'int32') {
        unset($t['integer_family']);
    }
}
unset($t);
file_put_contents($catalog_path, json_encode($without_family, JSON_THROW_ON_ERROR));
$observed = $session->observed;
Check::rejects(static fn() => $session->compile($manifest, $output), 'Unsupported implicit');
Check::check(($session->observed === $observed) && (Check::run($output) === 43), 'Revoked family membership invalidates conversions and preserves the previous executable');
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
Check::check($session->compile($manifest, $output)->completed, 'Repair of conversion metadata uses the same pipeline');

// Metadata-driven widths and names; actual zero extension for an unsigned high-bit value.
$unsigned = $catalog;
$unsigned['literal_types']['integer']['name'] = 'uint8';
$unsigned['entry_return_type']['name'] = 'uint32';
file_put_contents($catalog_path, json_encode($unsigned, JSON_THROW_ON_ERROR));
Check::edit($main, '$v uint32 = narrow(); return accept(narrow());');
Check::edit($answer, 'function accept($v uint32): uint32 { return $v; } function widen_unsigned($v uint8): uint32 { return $v; }');
Check::edit($value, 'function narrow(): uint8 { return 255; }');
$zero = $session->compile($manifest, $output);
Check::check(($zero->inputs->context->full_rebuild) && (Check::run($output) === 255)
    && (substr_count($zero->llvm->function_for($zero->types->entry->symbol->symbol_id)->ir, 'zext i8') === 2),
    'Unsigned widening uses zero extension and the configured native entry contract');
Check::probe($zero, Check::id($zero, 'widen_unsigned'), '255', '255');
$custom = $catalog;
foreach ($custom['types'] as &$t)
{
    if ($t['name'] === 'int32') {
        $t['name'] = 'Small';
        $t['bit_width'] = 13;
    }
    if ($t['name'] === 'int') {
        $t['name'] = 'Large';
        $t['bit_width'] = 37;
    }
}
unset($t);
$custom['literal_types']['integer']['name'] = 'Small';
$custom['entry_return_type']['name'] = 'Large';
file_put_contents($catalog_path, json_encode($custom, JSON_THROW_ON_ERROR));
Check::edit($main, 'return accept(narrow());');
Check::edit($answer, 'function accept($v Large): Large { return $v; }');
Check::edit($value, 'function narrow(): Small { return 44; }');
$generic = $session->compile($manifest, $output);
Check::check((Check::run($output) === 44) && str_contains(implode('', $generic->llvm->ir_by_file()), 'sext i13')
    && str_contains(implode('', $generic->llvm->ir_by_file()), 'to i37'), 'Different provider names and widths use the identical resolver and backend primitive');

// Corrupt unary graphs and consumer identities fail instead of looping or silently reusing an operand.
$bad_values = $body->values;
$conversion_id = array_key_first($conversion_values);
$v = $conversion_values[$conversion_id];
$bad_values[$conversion_id - 1] = new \check_bodies\typed_value($v->source_node_id, $v->type_id, value_kind::conversion,
    new \check_bodies\conversion_value($conversion_id, conversion_kind::integer_widen));
$bad = new \check_bodies\Checked_Body($body->owner, $body->names, $bad_values, $body->calls, $body->statements,
    $body->falls_through, $body->type_dependencies, $body->signature_dependencies, $body->local_types, $body->scopes, $body->arguments, $body->blocks);
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker($bad))->analyze(), 'cyclic checked conversion');
$ends = $lifetime->lifetimes;
$i = array_search($conversion_ends[0], $ends, true);
$e = $ends[$i];
$ends[$i] = new \analyze_lifetimes\value_lifetime($e->value_id, $e->statement_id, $e->end, $e->consumer_id + 1);
$bad_analysis = new \analyze_lifetimes\Analyzed_Body($body, $ends, $lifetime->reachable_statement_count, $lifetime->falls_through, $lifetime->local_lifetimes);
Check::rejects(static fn() => (new \lower\Lowering_Worker(new \lower\lowering_input($bad_analysis, $first->backend)))->lower(), 'does not match analyzed lifetime');
Check::rejects(static fn() => \prepare_backend\LLVM_Types::integer_conversion(\prepare_backend\integer_adaptation::sign_extend, 64, 32, '%x'), 'Invalid LLVM integer conversion widths');
echo "integer conversions ok: source-to-native signed/unsigned widening, all implicit boundaries, flat unary plans, consumers, metadata, unchanged reuse, body increment, rollback/repair and generic widths\n";
