<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Host integration fixture: production preparation, type bindings, engine and publisher.
[$script, $source, $output, $mode] = $argv;
$revision = (int)($argv[4] ?? 0);
$alignment = (int)($argv[5] ?? 8);
$composed = $mode === 'composed';
$source_arguments = ($argv[6] ?? '') === 'arguments';
$mutations = ($argv[6] ?? '') === 'mutations';
$iteration = ($argv[6] ?? '') === 'iteration';
$observations = ($argv[6] ?? '') === 'observations';
$compound = ($argv[6] ?? '') === 'compound';
$casts = $mutations || $iteration || $observations || $compound || ($argv[6] ?? '') === 'casts';
$binary = $casts || ($argv[6] ?? '') === 'binary';
$initializers = $binary || ($argv[6] ?? '') === 'initializers';
$indexing = $initializers || ($argv[6] ?? '') === 'indexing';
$members = $indexing || ($argv[6] ?? '') === 'members';
$fields = $members || (($argv[6] ?? '') === 'fields') || $source_arguments;
$metadata = new runtime_catalog_input();
foreach (Model::$runtime_metadata->modules as $module) { $metadata->modules->add($module->name, $module); }
$metadata->modules->add('type_proof', Runtime_Catalog_Reader::decode(file_get_contents(dirname(__DIR__) . '/runtime_types.json'), 'type_proof.json'));
if ($iteration) { $metadata->modules->add('protocol_proof', Runtime_Catalog_Reader::decode(file_get_contents(dirname(__DIR__) . '/runtime_protocols.json'), 'protocol_proof.json')); }
Compiler_Lifecycle::configure_runtime($metadata);
if (!is_dir($source)) { mkdir($source, 0777, true); }

$program = '$v vector<int>; $n vector<vector<int>>; $p parcel<vector<int>>; $t vector<receipt>; $a=count($v); $b=count($n); $c=unpack($p); $d=count($t);';
if ($fields) {
    $program = <<<'PHS'
struct Inner { public vector<int> $amounts; }
struct Record {
 public Inner $inner;
 public vector<vector<int>> $nested;
 public parcel<vector<int>> $parcel;
 public vector<receipt> $tracked;
 public function replace(const vector<int> &$input): void { $this->inner->amounts = $input; }
 public function copy_amounts(): vector<int> { return $this->inner->amounts; }
 public function forward(vector<int> &$output, const vector<int> &$input): void { copy_into($output, $input); }
}
function copy_into(vector<int> &$output, const vector<int> &$input): void { $output = $input; }
function &field(vector<int> &$values): vector<int> { return $values; }
function method(Record &$record, const vector<int> &$input): void { $record->replace($input); }
function reference(Record &$record): void { $values =& $record->inner->amounts; $same =& field($values); }
function demand(vector<int> &$v, vector<vector<int>> &$n, parcel<vector<int>> &$p, vector<receipt> &$t, int &$i): void {}
PHS;
}
if ($members) {
    $program .= '
function copy_receipt(const receipt &$value): receipt { return $value; }
function runtime_members(vector<int> &$amounts, vector<vector<int>> &$nested, parcel<vector<int>> &$parcel, vector<receipt> &$tracked, const vector<int> &$input, const receipt &$value): void {
 $amounts->append(5); $nested->append($input); $parcel->replace($input); $tracked->append($value); $tracked->append(copy_receipt($value));
}';
}
if ($indexing) {
    $program .= '
function index_key(vector<int> &$trace, int $key): int { $trace[] = 1; return $key; }
function index_inner_key(vector<int> &$trace, int $key): int { $trace[] = 4; return $key; }
function index_rhs(vector<int> &$trace, vector<vector<receipt>> &$rows, const vector<vector<receipt>> &$replacement, const receipt &$value): receipt { $trace[] = 2; $rows = $replacement; return $value; }
function index_work(vector<int> &$trace, vector<vector<receipt>> &$rows, const vector<vector<receipt>> &$replacement, hash<receipt,int> &$map, const receipt &$value): receipt {
 $rows[index_key($trace,0)][index_inner_key($trace,0)] = index_rhs($trace,$rows,$replacement,$value);
 $rows[index_key($trace,0)][] = $value;
 $map[7] = $value; $map[] = $value; unset($map[index_key($trace,7)]);
 $read = $rows[0][0]; return $read;
}
function unset_key(vector<int> &$trace, hash<hash<receipt,int>,int> &$tables, const hash<hash<receipt,int>,int> &$replacement): int { $trace[] = 3; $tables = $replacement; return 7; }
function unset_work(vector<int> &$trace, hash<hash<receipt,int>,int> &$tables, const hash<hash<receipt,int>,int> &$replacement): void {
 unset($tables[index_key($trace,10)][index_inner_key($trace,7)]);
 unset($tables[index_key($trace,99)][index_key($trace,7)]);
 unset($tables[index_key($trace,10)][unset_key($trace,$tables,$replacement)]);
}
function index_demand(vector<vector<receipt>> &$rows, hash<receipt,int> &$map, hash<hash<receipt,int>,int> &$tables): void {}';
}
if ($initializers) {
    $program .= '
struct InitialDefaults { public hash<receipt,int> $map = []; public vector<int> $numbers = []; }
function init_value(vector<int> &$trace, const receipt &$value): receipt { $trace[] = 2; return $value; }
function init_work(vector<int> &$trace, const receipt &$value): void {
 $defaults InitialDefaults;
 $list vector<receipt> = [$value, $value];
 $keyed hash<receipt,int> = [index_key($trace,1) => init_value($trace,$value), index_inner_key($trace,3) => init_value($trace,$value)];
 $nested vector<vector<receipt>> = [[$value], [$value, $value]];
 $nested_hash hash<vector<receipt>,int> = [1 => [$value, $value]];
 $numbers vector<int> = [3,4];
 $large vector<receipt> = [LARGE_INITIALIZER];
 if ($numbers[1] !== 4) { $trace[] = 99; }
}';
    $program = str_replace('LARGE_INITIALIZER', implode(', ', array_fill(0,40,'$value')), $program);
}
if ($binary) {
    $program .= '
enum BinaryTag: int { case First = 1; case Second = 2; }
function binary_step(int &$order, int $digit, const string &$value): string { $order = $order * 10 + $digit; return $value; }
function binary_change(string &$value, const string &$replacement): string { $value = $replacement; return $replacement; }
function binary_flag(int &$order, int $digit): bool { $order = $order * 10 + $digit; return true; }
function binary_work(const string &$text, const string &$other): int {
 $order = 0;
 $joined = binary_step($order,1,$text) . binary_step($order,2,$other);
 if ($order !== 12 || $joined !== ($text . $other)) { return 1; }
 $value = $text;
 if ($value === binary_change($value,$other)) { return 2; }
 if ($value !== $other) { return 3; }
 $order = 0;
 if (!(binary_flag($order,1) === binary_flag($order,2)) || $order !== 12) { return 4; }
 if ((true !== true) || !(false !== true)) { return 5; }
 $order = 0;
 $skip = false && binary_flag($order,1);
 $skip = true || binary_flag($order,2);
 if ($order !== 0) { return 6; }
 if (((17 + 3) * 2 - 4) / 3 !== 12 || 17 % 3 !== 2) { return 7; }
 if ((2 ** 3) !== 8 || (2 << 3) !== 16 || (16 >> 2) !== 4) { return 8; }
 if ((7 & 3) !== 3 || (4 | 1) !== 5 || (7 ^ 3) !== 4) { return 9; }
 if ((2 <=> 3) !== -1 || (3 <=> 3) !== 0 || (4 <=> 3) !== 1) { return 10; }
 if (!(BinaryTag::First == BinaryTag::First) || !(BinaryTag::First != BinaryTag::Second)) { return 11; }
 return 0;
}
function binary_failure(vector<int> &$trace, int $zero): int { return index_key($trace,1) / $zero + index_inner_key($trace,2); }
function binary_rhs_failure(vector<int> &$trace, int $zero): int { return index_key($trace,1) + (index_inner_key($trace,2) / $zero); }
';
}
if ($casts) {
    $program .= '
function cast_text(vector<int> &$trace, const string &$value): string { $trace[] = 1; return $value; }
function cast_number(vector<int> &$trace, int $value): int { $trace[] = 2; return $value; }
function cast_work(vector<int> &$trace, const string &$digits): int {
 $a = (int)cast_text($trace,$digits);
 $b = (string)cast_number($trace,42);
 if ($a !== 42 || $b !== "42") { return 1; }
 $small vector<uint8> = [(uint8)cast_number($trace,7)];
 if ((int)$small[0] !== 7) { return 2; }
 $f = (float)7;
 if ((int)$f !== 7 || (bool)0 || !(bool)1) { return 3; }
 $text = "value=" . cast_number($trace,9);
 if ($text !== "value=9") { return 4; }
 $map hash<int,int> = [(int)cast_text($trace,$digits) => (int8)cast_number($trace,8)];
 if ($map[42] !== 8) { return 5; }
 return 0;
}
function cast_failure(vector<int> &$trace, const string &$bad): int { return (int)cast_text($trace,$bad) + index_inner_key($trace,7); }
function cast_key_failure(vector<int> &$trace): void { $map hash<int,int> = [(uint8)cast_number($trace,256) => index_inner_key($trace,7)]; }
function cast_value_failure(vector<int> &$trace): void { $map hash<int,int> = [index_key($trace,1) => (uint8)cast_number($trace,256), index_inner_key($trace,7) => 1]; }
';
}
if ($mutations) {
    $program .= <<<'PHS'
struct MutationState { public int $number; public int $visits; }
function &mutation_receiver(MutationState &$state): MutationState { ++$state->visits; return $state; }
function mutation_work(vector<int> &$trace): int {
 $value = 10; $alias =& $value;
 $a = $alias++; $b = ++$value; $c = $alias--; $d = --$value;
 if ($a !== 10 || $b !== 12 || $c !== 12 || $d !== 10 || $alias !== 10) { return 1; }
 $result = $value++ + ++$value;
 if ($result !== 22 || $value !== 12) { return 2; }
 $state MutationState; $state->number = 5; $state->visits = 0;
 $old = mutation_receiver($state)->number++; $trace[] = $state->visits;
 $new = --mutation_receiver($state)->number; $trace[] = $state->visits;
 if ($old !== 5 || $new !== 5 || $state->number !== 5) { return 3; }
 $f float = 1.5; $before = $f++; $after = --$f;
 if ($before != 1.5 || $after != 1.5 || $f != 1.5) { return 4; }
 return 0;
}
function mutation_carrier(?float &$value): ?float { return $value++; }
PHS;
}
if ($iteration) {
    $program .= <<<'PHS'
function iteration_source(vector<int> &$trace): vector<int> { $trace[] = 9; return [2,3,4]; }
function iteration_work(vector<int> &$trace): int {
 $sum = 0;
 foreach (iteration_source($trace) as $key => $value) {
  if ($key === 1) { continue; }
  $sum = $sum + $value;
 }
 $map hash<int,int> = [8 => 20, 3 => 40];
 foreach ($map as $key => $value) { $trace[] = $key; $sum = $sum + $value; }
 $nested vector<vector<int>> = [[1,2],[3]];
 foreach ($nested as $row) { foreach ($row as $value) { $sum = $sum + $value; } }
 $custom tray<int> = [5,6];
 foreach ($custom as $key => $value) { $sum = $sum + $key + $value; }
 $empty vector<int>;
 $value = 100;
 foreach ($empty as $value) { return -1; }
 if ($value !== 100) { return -2; }
 foreach ($map as $key => $value) { $sum = $sum + $key; break; }
 return $sum;
}
function iteration_tracked(const vector<receipt> &$values): int {
 $sum = 0; foreach ($values as $item) { $sum = $sum + 1; } return $sum;
}
function iteration_return(const vector<receipt> &$values): int {
 foreach ($values as $item) { return 1; } return 0;
}
PHS;
}
if ($observations) {
    $program .= '
class ObservationOwner { public vector<int> $items = [4,5]; public string $text = "owner text"; }
function observation_owner(vector<int> &$trace): ObservationOwner { $trace[] = 1; return new ObservationOwner(); }
function observation_vector(vector<int> &$trace): vector<int> { $trace[] = 2; return [1,2,3]; }
function observation_text(vector<int> &$trace): string { $trace[] = 3; return "Aé🙂"; }
function observation_work(vector<int> &$trace, const vector<receipt> &$tracked, const parcel<vector<int>> &$parcel): int {
 $empty vector<int>;
 $map hash<int,int> = [1 => 8, 2 => 9];
 $nested vector<vector<int>> = [[1],[2,3]];
 if (count($empty) !== 0 || count($map) !== 2 || count($nested) !== 2 || count($tracked) !== 1 || count(unpack($parcel)) !== 2) { return 1; }
 $result = count(observation_owner($trace)->items) + strlen(observation_owner($trace)->text);
 if ($result !== 12) { return 2; }
 $result = count(observation_vector($trace)) + strlen(observation_text($trace));
 if ($result !== 10) { return 3; }
 return 0;
}
';
}
if ($compound) {
    $program .= '
function compound_rhs(vector<int> &$values, vector<int> &$trace): int {
 $trace[] = 2; for ($i = 0; $i < 100; $i = $i + 1) { $values[] = $i; } $values[0] = 100; return 3;
}
function compound_replace(vector<vector<int>> &$values, vector<int> &$trace): int { $trace[] = 3; $values = [[100]]; return 4; }
function compound_work(vector<int> &$trace): int {
 $values vector<int> = [10];
 $result = ($values[index_key($trace,0)] += compound_rhs($values,$trace));
 if ($result !== 13 || $values[0] !== 13 || $values[100] !== 99) { return 1; }
 $nested vector<vector<int>> = [[5]]; $copy = $nested;
 $nested[index_key($trace,0)][index_inner_key($trace,0)] *= compound_replace($nested,$trace);
 if ($nested[0][0] !== 20 || $copy[0][0] !== 5) { return 2; }
 $map hash<int,int> = [1 => 8]; $map[1] -= 3;
 if ($map[1] !== 5) { return 3; }
 $words hash<string> = {x: "start-with-a-long-string-that-exercises-runtime-string-storage"}; $words["x"] .= "-end";
 if ($words["x"] !== "start-with-a-long-string-that-exercises-runtime-string-storage-end") { return 4; }
 return 0;
}
function compound_missing(vector<int> &$trace): void { $map hash<int,int>; $map[index_key($trace,1)] += index_inner_key($trace,2); }
function compound_remove(hash<int,int> &$map, vector<int> &$trace): int { $trace[] = 2; unset($map[1]); return 1; }
function compound_map(): hash<int,int> { return [1 => 10]; }
function compound_present(const hash<int,int> &$map): bool { return isset($map[1]); }
function compound_divide(vector<int> &$values, vector<int> &$trace): void { $values[index_key($trace,0)] /= index_inner_key($trace,0); }
function compound_deleted(hash<int,int> &$map, vector<int> &$trace): void { $map[index_key($trace,1)] += compound_remove($map,$trace); }
';
}
if ($source_arguments) {
    $program .= '
class MyClass { public vector<int> $amounts; public function duplicate(): vector<int> { return $this->amounts; } }
function source_demand(vector<Record> &$rows, parcel<Record> &$box, vector<MyClass> &$owners): void {}
function source_append(vector<MyClass> &$owners, const MyClass &$value): void { $owners[] = $value; }
function source_store(vector<MyClass> &$owners, const MyClass &$value): void { $owners[0] = $value; }
function source_read(const vector<MyClass> &$owners): MyClass { return $owners[0]; }';
    if ($revision !== 0) { $program = str_replace('return $this->inner->amounts;', 'if (false) { return $this->inner->amounts; } return $this->inner->amounts;', $program); }
}
file_put_contents($source . '/main.phs', $program);
$compiler = new Compiler();
$compiler->cpp_lowering->prelower_templates = $fields && $composed;
$compiler->cpp_lowering->concrete_type_substitution = $fields && $composed;
$compiler->init([$source]);
$compiler->exec_cpp();
$body = Model::syntax_files()[0]->root->single_body()->body;
$version = $body->work()->version;
$types = [];
if (!$fields) {
foreach (['Vector', 'Nested', 'Parcel', 'TrackedVector'] as $offset => $name) {
    $types[$name] = $body->statements[$offset + 4]->expression->value->require_call_preparation()->signature->parameters[0]->type;
}
$types['Int'] = $body->statements[4]->expression->value->require_call_preparation()->type;
} else {
    $parameters = Model::$global_scope->functions_named('demand')[0]->syntax()->require_preparation()->signature->parameters;
    foreach (['Vector', 'Nested', 'Parcel', 'TrackedVector', 'Int'] as $index => $name) { $types[$name] = $parameters[$index]->type; }
    if ($source_arguments) {
        $parameters = Model::$global_scope->functions_named('source_demand')[0]->syntax()->require_preparation()->signature->parameters;
        foreach (['Records', 'RecordParcel', 'Owners'] as $index => $name) { $types[$name] = $parameters[$index]->type; }
        $types['Handle'] = Type_Queries::canonical($types['Owners'])->arguments()[0];
    }
    if ($indexing) {
        $parameters = Model::$global_scope->functions_named('index_demand')[0]->syntax()->require_preparation()->signature->parameters;
        foreach (['TrackedRows', 'TrackedHash', 'NestedHash'] as $index => $name) { $types[$name] = $parameters[$index]->type; }
    }
    if ($mutations) { $types['NullableFloat'] = Model::$global_scope->functions_named('mutation_carrier')[0]->syntax()->require_preparation()->signature->parameters[0]->type; }
    $snapshot = static function () { $r=[]; foreach(Model::$cpp_files as $a) {$r[$a->file_name]=$a->text;} return $r; };
    $before = $snapshot();
    $fragments=[]; foreach(Model::$cpp_output_program->fragments as $owner) {$fragments[] = Model::$cpp_output_program->fragments[$owner];}
    $compiler->cpp();
    if ($snapshot() !== $before) { throw new \LogicException('Concrete source fragment replay changed output'); }
    $index=0; foreach(Model::$cpp_output_program->fragments as $owner) {
        if (Model::$cpp_output_program->fragments[$owner] !== $fragments[$index++]) { throw new \LogicException('No-op regenerated a source fragment'); }
    }
    if ($members && $composed) {
        $removed = preg_replace('/function runtime_members\((.*?)\): void \{.*?\n\}/s', 'function runtime_members($1): void {}', $program);
        file_put_contents($source . '/main.phs', $removed);
        $compiler->update_cpp([$source . '/main.phs']);
        foreach (Model::$cpp_files as $artifact) {
            if (str_starts_with($artifact->file_name, 'specializations/concrete_') && str_contains($artifact->text, 'void append(')) {
                throw new \LogicException('Removed member requests survived fragment replacement');
            }
        }
        file_put_contents($source . '/main.phs', $program);
        $compiler->update_cpp([$source . '/main.phs']);
        if ($snapshot() !== $before) { throw new \LogicException('Member request restoration changed output'); }
    }
    if ($initializers) {
        $removed = substr($program, 0, strpos($program, '
struct InitialDefaults'));
        if ($binary) { $removed .= substr($program, strpos($program, '
enum BinaryTag')); }
        if ($casts) { $removed = substr($removed,0,strpos($removed,'
function cast_text')); }
        file_put_contents($source . '/main.phs', $removed);
        $compiler->update_cpp([$source . '/main.phs']);
        foreach (Model::$cpp_files as $artifact) {
            if (str_starts_with($artifact->file_name, 'specializations/runtime_initialize_')) {
                throw new \LogicException('Removed initializer requests survived fragment replacement');
            }
        }
        file_put_contents($source . '/main.phs', $program);
        $compiler->update_cpp([$source . '/main.phs']);
        if ($snapshot() !== $before) { throw new \LogicException('Initializer request restoration changed output'); }
    }
    if ($binary && $composed) {
        $removed = preg_replace('/function binary_work\(.*?\n\}/s', '', $program);
        if ($casts) { $removed = substr($removed,0,strpos($removed,'
function cast_text')); }
        file_put_contents($source . '/main.phs', $removed);
        $compiler->update_cpp([$source . '/main.phs']);
        foreach (Model::$cpp_output_program->fragments as $owner) {
            foreach (Model::$cpp_output_program->fragments[$owner]->runtime_operations as $request) {
                if ($request->kind === cpp_runtime_operation_kind::binary && $request->decision->operation === operator_operation::string_concatenation) {
                    throw new \LogicException('Removed binary operation retained a helper demand');
                }
            }
        }
        file_put_contents($source . '/main.phs', $program);
        $compiler->update_cpp([$source . '/main.phs']);
        if ($snapshot() !== $before) { throw new \LogicException('Binary request restoration changed output'); }
    }
    if ($casts && $composed) {
        $removed = substr($program,0,strpos($program,'
function cast_text'));
        file_put_contents($source . '/main.phs', $removed);
        $compiler->update_cpp([$source . '/main.phs']);
        foreach (Model::$cpp_output_program->fragments as $owner) {
            foreach (Model::$cpp_output_program->fragments[$owner]->runtime_operations as $request) {
                if ($request->kind === cpp_runtime_operation_kind::conversion) {
                    throw new \LogicException('Removed casts retained a conversion helper');
                }
                if ($mutations && $request->kind === cpp_runtime_operation_kind::concrete_mutation) { throw new \LogicException('Removed mutation retained a helper'); }
                if ($iteration && $request->kind === cpp_runtime_operation_kind::concrete_iteration) { throw new \LogicException('Removed iteration retained a helper'); }
                if ($observations && ($request->kind === cpp_runtime_operation_kind::observation || $request->kind === cpp_runtime_operation_kind::runtime_call)) {
                    throw new \LogicException('Removed observations retained a helper');
                }
                if ($compound && $request->kind === cpp_runtime_operation_kind::concrete_index && $request->spelling === 'index_existing_probe') {
                    throw new \LogicException('Removed compounds retained an existing-element helper');
                }
            }
        }
        file_put_contents($source . '/main.phs', $program);
        $compiler->update_cpp([$source . '/main.phs']);
        if ($snapshot() !== $before) { throw new \LogicException('Cast restoration changed output'); }
    }
    $stable_owner = Model::$global_scope->functions_named('field')[0]->syntax()->body->work();
    $stable_fragment = Model::$cpp_output_program->fragments[$stable_owner];
    $edited = str_replace('$this->inner->amounts = $input;', 'if (false) { $this->inner->amounts = $input; } $this->inner->amounts = $input;', $program);
    file_put_contents($source . '/main.phs', $edited);
    $compiler->update_cpp([$source . '/main.phs']);
    if (Model::$cpp_output_program->fragments[$stable_owner] !== $stable_fragment) { throw new \LogicException('Member edit regenerated unrelated function'); }
    if ($snapshot() === $before) { throw new \LogicException('Member body edit did not change emission'); }
    file_put_contents($source . '/main.phs', $program);
    $compiler->update_cpp([$source . '/main.phs']);
    if ($snapshot() !== $before) { throw new \LogicException('Member edit restoration changed output'); }
    if ($composed) {
        $settled = $stable_owner->version;
        $compiler->cpp_lowering->prelower_templates = false;
        $compiler->cpp();
        if ($snapshot() === $before) { throw new \LogicException('Option switch reused concrete source fragments'); }
        $compiler->cpp_lowering->prelower_templates = true;
        $compiler->cpp_lowering->runtime_template_abi = true; // Composition takes precedence over comparison mode.
        $compiler->cpp();
        if (($snapshot() !== $before) || ($stable_owner->version !== $settled)) { throw new \LogicException('Option replay changed preparation or output'); }
    }

}
$engine = new CPP_Specializations(false, $composed);
$header = "#pragma once\n#include \"instrument.hpp\"\n";
$support = "#include \"interface.hpp\"\n#include <type_traits>\n#include <utility>\n#include <memory>\n";
$artifacts = new Storage();
foreach ($types as $name => $type) {
    $mapping = CPP_Types::representation($type, new cpp_generation_context($composed ? $engine : null));
    $header .= CPP_Artifacts::includes($mapping->headers, '') . 'using ' . $name . ' = ' . $mapping->spelling . ";\n";
    $native = $composed ? CPP_Types::implementation_representation($type, $engine) : CPP_Types::representation($type);
    $support .= CPP_Artifacts::includes($native->headers, '') . 'using Native_' . $name . ' = ' . $native->spelling . ";\n";
    foreach (['', 'const '] as $qualifier) {
        $expression = $mapping->opaque ? '*std::launder(static_cast<' . $qualifier . 'Native_' . $name . '*>(value.address()))' : 'value';
        $support .= $qualifier . 'Native_' . $name . '& unwrap(' . $qualifier . $name . '& value) { return ' . $expression . "; }\n";
    }
}
if ($composed) {
    if ($source_arguments) {
        foreach (Model::$cpp_files as $artifact) {
            if ($artifact->kind() === cpp_artifact_kind::header && !str_starts_with($artifact->file_name, 'specializations/')) { $artifacts->append($artifact); }
        }
    }
    if ($members || $source_arguments) {
        foreach (Model::$cpp_output_program->fragments as $owner) {
            foreach (Model::$cpp_output_program->fragments[$owner]->runtime_operations as $request) { $engine->request_operation($request); }
        }
    }
    $engine->append_artifacts($artifacts);
    // Request order and repeated requests must not change generated bytes or semantic state.
    $other = new CPP_Specializations(false, $composed);
    foreach (array_reverse($types) as $type) { CPP_Types::representation($type, new cpp_generation_context($other)); CPP_Types::representation($type, new cpp_generation_context($other)); }
    $replayed = new Storage();
    if ($source_arguments) {
        foreach (Model::$cpp_files as $artifact) {
            if ($artifact->kind() === cpp_artifact_kind::header && !str_starts_with($artifact->file_name, 'specializations/')) { $replayed->append($artifact); }
        }
    }
    if ($members || $source_arguments) {
        foreach (Model::$cpp_output_program->fragments as $owner) {
            foreach (Model::$cpp_output_program->fragments[$owner]->runtime_operations as $request) { $other->request_operation($request); }
        }
    }
    $other->append_artifacts($replayed);
    $snapshot = static function ($items) { $result=[]; foreach ($items as $item) { $result[$item->file_name]=$item->text; } return $result; };
    if ($snapshot($artifacts) !== $snapshot($replayed)) { throw new \LogicException('Physical request replay changed artifacts'); }
    $probes = 0;
    foreach ($artifacts as $artifact) { if ($artifact->kind() === cpp_artifact_kind::layout_probe) { $probes++; } }
    if ($probes !== ($source_arguments ? 4 : 3)) { throw new \LogicException('Expected integer, container and nested-container layout batches'); }
}
if ($version !== $body->work()->version) { throw new \LogicException('Physical substitution changed preparation'); }
if ($fields) {
    $generated = Model::$cpp_files;
    $expected_support = []; foreach ($artifacts as $artifact) { if (str_starts_with($artifact->file_name, 'specializations/')) { $expected_support[$artifact->file_name] = $artifact->text; } }
    $actual_support = [];
    $artifacts = new Storage();
    foreach ($generated as $artifact) {
        if (str_starts_with($artifact->file_name, 'specializations/')) { $actual_support[$artifact->file_name] = $artifact->text; }
        elseif ($composed && (str_contains($artifact->text, 'scpp::vector_t<') || str_contains($artifact->text, 'scpp::int_t<'))) {
            throw new \LogicException('Native template spelling leaked into source emission');
        }
        if ($artifact->file_name !== 'entry.cpp') { $artifacts->append($artifact); }
    }
    ksort($expected_support); ksort($actual_support);
    if ($expected_support !== $actual_support) { throw new \LogicException('Source fragment requests did not publish the demanded support'); }
    $header .= '#include "' . CPP_Artifacts::type_header('Record') . "\"\n";
    foreach (['field', 'method', 'reference'] as $function) {
        $header .= '#include "' . CPP_Artifacts::callable_header($function) . "\"\n";
    }
    $header .= 'using Record = ' . CPP_Generator::source_name('record', 'Record') . ";\n";
}
$header .= <<<'CPP'
void seed(Vector&);
void append(Nested&, const Vector&);
Vector& row(Nested&);
const Vector& row(const Nested&);
Vector& contents(Parcel&);
void mutate(Vector&);
long read(const Vector&);
void seed_tracked(TrackedVector&);
void verify();
struct Record { Nested nested; Parcel parcel; TrackedVector tracked; };
CPP;
$support .= <<<'CPP'
void verify() {
    static_assert(std::is_same_v<decltype(unwrap(std::declval<Nested&>()).at(0)), Vector&>);
    static_assert(std::is_same_v<decltype(unwrap(std::declval<Parcel&>()).value), Vector>);
    static_assert(std::is_same_v<decltype(unwrap(std::declval<Vector&>()).at(0)), Int&>);
    static_assert(std::is_nothrow_move_constructible_v<Record>);
}
void seed(Vector& value) { unwrap(value).append(Int(7)); unwrap(value).append(Int(9)); }
void append(Nested& value, const Vector& input) { unwrap(value).append(input); }
Vector& row(Nested& value) { return unwrap(value).at(0); }
const Vector& row(const Nested& value) { return unwrap(value).at(0); }
Vector& contents(Parcel& value) { return unwrap(value).value; }
void mutate(Vector& value) { unwrap(value).at(0) = Int(21); }
long read(const Vector& value) { return unwrap(value).at(0).native_value(); }
void seed_tracked(TrackedVector& value) {
    runtime_type_test::receipt first{}, second{};
    unwrap(value).append(first); unwrap(value).append(second);
}
CPP;
$main = <<<'CPP'
#include "interface.hpp"
#include <utility>
int main() {
    verify();
    begin(); {
        Record record;
        Vector input;
        seed(input);
        append(record.nested, input);
        seed(contents(record.parcel));
        seed_tracked(record.tracked);
        Record copy(record);
        Record moved(std::move(copy));
        mutate(row(moved.nested));
        require(read(row(record.nested)) == 7 && read(row(moved.nested)) == 21);
        require(read(contents(moved.parcel)) == 7);
    } report("workload");
    require(live_values == 0);
    {
        Record record;
        Vector input;
        seed(input); append(record.nested, input); seed(contents(record.parcel));
        seed_tracked(record.tracked);
        begin();
        const Record& readonly = record;
        require(&row(record.nested) == &row(readonly.nested));
        mutate(row(record.nested)); mutate(contents(record.parcel));
        require(read(row(readonly.nested)) == 21 && read(contents(record.parcel)) == 21);
        report("references");
        begin();
        copies_before_throw=1;
        bool caught=false;
        try { Record copy(record); } catch(const CopyFailure&) { caught=true; }
        copies_before_throw=-1;
        require(caught && live_values==2);
        report("copy-failure");
    }
    require(live_values==0);
    return REVISION;
}
CPP;
if ($fields) {
    $header = str_replace('struct Record { Nested nested; Parcel parcel; TrackedVector tracked; };', '', $header);
    // The harness only drives generated declarations/calls; no replacement source implementation.
    $field_function = CPP_Callables::source_reference(Model::$global_scope->functions_named('field')[0]);
    $method_function = CPP_Callables::source_reference(Model::$global_scope->functions_named('method')[0]);
    $main = str_replace('append(record.nested, input);',
        'append(record.nested, input); ' . $method_function . '(record, input);', $main);
    $main = str_replace('        Record copy(record);',
        'Vector copied = record.' . CPP_Generator::source_name('method', 'copy_amounts') . '(); require(read(copied) == 7); record.' . CPP_Generator::source_name('method', 'forward') . '(copied, input); Record copy(record);', $main);
    $reference_function = CPP_Callables::source_reference(Model::$global_scope->functions_named('reference')[0]);
    $main = str_replace('report("references");',
        $reference_function . '(record); auto& actual = ' . $field_function . '(record.inner.amounts); require(&actual == &record.inner.amounts); mutate(actual); require(read(record.inner.amounts) == 21); report("references");', $main);
    foreach (['nested', 'parcel', 'tracked', 'inner', 'amounts'] as $field_name) {
        $main = str_replace('.' . $field_name, '.' . CPP_Generator::source_name('field', $field_name), $main);
    }
}
if ($source_arguments) {
    $header .= "\nvoid source_argument_proof();\n";
    $support .= "\n" . '#include "' . CPP_Artifacts::type_header('MyClass') . "\"\n";
    $support .= 'using Payload = ' . CPP_Generator::source_name('record', 'MyClass') . ";\n";
    $support .= 'Handle make_handle() { return Handle(std::make_shared<Payload>()); }' . "\n";
    foreach (['source_append', 'source_store', 'source_read'] as $function) {
        $support .= '#include "' . CPP_Artifacts::callable_header($function) . "\"\n";
    }
    $scenario = <<<'CPP'
void source_argument_proof() {
    static_assert(std::is_same_v<decltype(unwrap(std::declval<Records&>()).at(0)), Record&>);
    static_assert(std::is_same_v<decltype(unwrap(std::declval<RecordParcel&>()).value), Record>);
    static_assert(std::is_same_v<decltype(unwrap(std::declval<Owners&>()).at(0)), Handle&>);
    begin(); {
        Record record;
        seed(record.inner.amounts);
        seed_tracked(record.tracked);
        Records rows;
        unwrap(rows).append(record);
        Records copy(rows);
        mutate(unwrap(copy).at(0).inner.amounts);
        require(read(unwrap(rows).at(0).inner.amounts) == 7);
        require(read(unwrap(copy).at(0).inner.amounts) == 21);
        RecordParcel box;
        unwrap(box).value = record;
        mutate(unwrap(box).value.inner.amounts);
        require(read(record.inner.amounts) == 7);
    } report("source-values");
    require(live_values == 0);
    begin(); {
        Handle owner = make_handle();
        seed(unwrap(owner)->amounts);
        Owners owners;
        SOURCE_APPEND(owners, owner);
        SOURCE_STORE(owners, owner);
        auto same = SOURCE_READ(owners);
        require(unwrap(same).get() == unwrap(owner).get() && unwrap(owner).use_count() == 3);
        Owners copy(owners);
        mutate(unwrap(unwrap(copy).at(0))->amounts);
        require(read(unwrap(owner)->amounts) == 21);
        require(unwrap(unwrap(copy).at(0)).operator->() == unwrap(owner).operator->());
    } report("source-shared");
    require(live_values == 0);
    {
        Records rows;
        Record record;
        seed(record.inner.amounts); seed_tracked(record.tracked);
        unwrap(rows).append(record);
        begin();
        const Records& view = rows;
        require(&unwrap(rows).at(0) == &unwrap(view).at(0));
        mutate(unwrap(rows).at(0).inner.amounts);
        require(read(unwrap(view).at(0).inner.amounts) == 21);
        report("source-references");
    }
    require(live_values == 0);
}
CPP;
    foreach (['source_append', 'source_store', 'source_read'] as $function) {
        $scenario = str_replace(strtoupper($function), CPP_Callables::source_reference(Model::$global_scope->functions_named($function)[0]), $scenario);
    }

    foreach (['inner', 'amounts', 'tracked'] as $field_name) {
        $scenario = str_replace('.' . $field_name, '.' . CPP_Generator::source_name('field', $field_name), $scenario);
        $scenario = str_replace('->' . $field_name, '->' . CPP_Generator::source_name('field', $field_name), $scenario);
    }
    $support .= $scenario;
    $main = str_replace('verify();', 'verify(); source_argument_proof();', $main);
}
if ($members) {
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('runtime_members')[0]);
    $support .= "\n" . '#include "' . CPP_Artifacts::callable_header('runtime_members') . "\"\n";
    $header .= "\nvoid member_proof();\n";
    $support .= 'void member_proof() { begin(); { Vector amounts, input; Nested nested; Parcel parcel; TrackedVector tracked; runtime_type_test::receipt value; seed(input); '
        . $function . '(amounts, nested, parcel, tracked, input, value); '
        . 'require(unwrap(amounts).at(0).native_value() == 5); require(read(row(nested)) == 7); require(read(contents(parcel)) == 7); require(unwrap(tracked).size() == 2); '
        . '} report("members"); require(live_values == 0); }';
    $main = str_replace('verify();', 'verify(); member_proof();', $main);
}
if ($indexing) {
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('index_work')[0]);
    $support .= "\n#include <scpp/keyed_path.hpp>\n" . '#include "' . CPP_Artifacts::callable_header('index_work') . "\"\n";
    foreach ($artifacts as $artifact) {
        if (str_contains($artifact->file_name, 'runtime_concrete_index_') && $artifact->kind() === cpp_artifact_kind::header) {
            $support .= '#include "' . $artifact->file_name . "\"\n";
        }
    }
    $unset_function = CPP_Callables::source_reference(Model::$global_scope->functions_named('unset_work')[0]);
    $support .= '#include "' . CPP_Artifacts::callable_header('unset_work') . "\"\n";
    $header .= "\nvoid indexing_proof();\n";
    $support .= 'void indexing_proof() { { TrackedHash edges; runtime_type_test::receipt value; unwrap(edges).set(Int{-4}, value); require(unwrap(edges).append(value).native_value()==0); unwrap(edges).set(Int{9223372036854775807LL}, value); bool overflow=false; try { (void)unwrap(edges).append(value); } catch(const std::overflow_error&) { overflow=true; } require(overflow); } require(live_values==0); begin(); { Vector trace; TrackedRows rows, replacement; TrackedVector row; seed_tracked(row); unwrap(rows).append(row); unwrap(replacement).append(row); TrackedHash map; runtime_type_test::receipt value; value.payload.value=91; '
        . 'auto result = ' . $function . '(trace, rows, replacement, map, value); '
        . 'require(result.payload.value==91); require(unwrap(trace).size()==5); require(unwrap(trace).at(0).native_value()==1 && unwrap(trace).at(1).native_value()==4 && unwrap(trace).at(2).native_value()==2); '
        . 'require(unwrap(map).size()==1); require(unwrap(map).at(Int{8}).payload.value==91); require(unwrap(unwrap(rows).at(0)).at(0).payload.value==91); '
        . 'require(unwrap(unwrap(replacement).at(0)).at(0).payload.value!=91); require(unwrap(unwrap(rows).at(0)).size()==3); '
        . 'NestedHash tables, table_replacement; TrackedHash child, new_child; unwrap(child).set(Int{7}, value); unwrap(new_child).set(Int{9}, value); unwrap(tables).set(Int{10}, child); unwrap(table_replacement).set(Int{10}, new_child); '
        . $unset_function . '(trace, tables, table_replacement); require(unwrap(trace).size()==10); const long expected_trace[]={1,4,2,1,1,1,4,1,1,3}; for(std::size_t i=0;i<10;++i) { require(unwrap(trace).at(i).native_value()==expected_trace[i]); } require(unwrap(tables).size()==1); require(unwrap(unwrap(tables).at(Int{10})).size()==1); require(unwrap(unwrap(tables).at(Int{10})).at(Int{9}).payload.value==91); '
        . '} report("indexing"); require(live_values==0); '
        . '{ TrackedRows rows; TrackedVector row; seed_tracked(row); unwrap(rows).append(row); Int key{0}; begin(); const auto& probe=scpp::index_path_probe(rows,key); auto& target=scpp::index_path_descend(rows,key); require(&probe==&target && &target==&unwrap(rows).at(0)); report("indexed-references"); } require(live_values==0); '
        . 'for(int failure=0;failure<3;++failure) { begin(); { Vector trace; TrackedRows rows,replacement; TrackedVector row; seed_tracked(row); unwrap(rows).append(row); unwrap(replacement).append(row); TrackedHash map; runtime_type_test::receipt value; copies_before_throw=failure; bool caught=false; try { (void)'
        . $function . '(trace,rows,replacement,map,value); } catch(const CopyFailure&) { caught=true; } copies_before_throw=-1; require(caught && unwrap(map).size()==0 && unwrap(trace).size()==(failure==0 ? 3 : 4)); } report("indexed-copy-failure"); require(live_values==0); } }';
    $main = str_replace('verify();', 'verify(); indexing_proof();', $main);
}
if ($initializers) {
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('init_work')[0]);
    $support .= "\n" . '#include "' . CPP_Artifacts::callable_header('init_work') . "\"\n";
    $header .= "\nvoid initializer_proof();\n";
    $support .= 'void initializer_proof() {
      begin(); { Vector trace; runtime_type_test::receipt value; ' . $function . '(trace,value);
        require(unwrap(trace).size()==4); const long expected[]={1,2,4,2};
        for(int i=0;i<4;++i) require(unwrap(trace).at(i).native_value()==expected[i]);
      } report("initializers"); require(live_values==0);
      bool completed=false;
      for(int budget=0;budget<512;++budget) {
        begin(); { Vector trace; runtime_type_test::receipt value; copies_before_throw=budget;
          try { ' . $function . '(trace,value); completed=true; } catch(const CopyFailure&) {}
          copies_before_throw=-1;
          require(unwrap(trace).size()<=4); const long expected[]={1,2,4,2};
          for(std::size_t i=0;i<unwrap(trace).size();++i) require(unwrap(trace).at(i).native_value()==expected[i]);
          std::printf("initializer-prefix %zu\n", unwrap(trace).size());
        } report("initializer-copy-budget"); require(live_values==0);
        if(completed) break;
      } require(completed);
    }';
    $main = str_replace('verify();', 'verify(); initializer_proof();', $main);
}
if ($binary) {
    $header .= "\nvoid binary_proof();\n";
    foreach (['binary_work','binary_failure','binary_rhs_failure'] as $name) {
        $support .= "\n" . '#include "' . CPP_Artifacts::callable_header($name) . "\"\n";
    }
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('binary_work')[0]);
    $failure = CPP_Callables::source_reference(Model::$global_scope->functions_named('binary_failure')[0]);
    $rhs_failure = CPP_Callables::source_reference(Model::$global_scope->functions_named('binary_rhs_failure')[0]);
    $support .= 'void binary_proof() {
      scpp::string_t text{"a long string value that exceeds short string storage"};
      scpp::string_t other{"another long string value that exceeds short string storage"};
      begin(); { auto result=' . $function . '(text,other); require(result.native_value()==0); } report("binary");
      // Runtime error stack-trace allocations are diagnostic policy, not boundary payload work.
      { Vector trace; bool caught=false; try { (void)' . $failure . '(trace,Int{0}); } catch(const std::exception&) { caught=true; }
        require(caught && unwrap(trace).size()==1 && unwrap(trace).at(0).native_value()==1); }
      { Vector trace; bool caught=false; try { (void)' . $rhs_failure . '(trace,Int{0}); } catch(const std::exception&) { caught=true; }
        require(caught && unwrap(trace).size()==2 && unwrap(trace).at(0).native_value()==1 && unwrap(trace).at(1).native_value()==4); }
    }';
    $main = str_replace('verify();', 'verify(); binary_proof();', $main);
}
if ($casts) {
    $header .= "\nvoid cast_proof();\n";
    foreach (['cast_work','cast_failure','cast_key_failure','cast_value_failure'] as $name) {
        $support .= "\n" . '#include "' . CPP_Artifacts::callable_header($name) . "\"\n";
    }
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('cast_work')[0]);
    $failure = CPP_Callables::source_reference(Model::$global_scope->functions_named('cast_failure')[0]);
    $key_failure = CPP_Callables::source_reference(Model::$global_scope->functions_named('cast_key_failure')[0]);
    $value_failure = CPP_Callables::source_reference(Model::$global_scope->functions_named('cast_value_failure')[0]);
    $support .= 'void cast_proof() {
      scpp::string_t digits{"42"}, bad{"invalid integer"};
      begin(); { Vector trace; auto result=' . $function . '(trace,digits); require(result.native_value()==0);
        const long expected[]={1,2,2,2,1,2}; require(unwrap(trace).size()==6);
        for(int i=0;i<6;++i) require(unwrap(trace).at(i).native_value()==expected[i]);
      } report("casts");
      { Vector trace; bool caught=false; try { (void)' . $failure . '(trace,bad); } catch(const std::exception&) { caught=true; }
        require(caught && unwrap(trace).size()==1 && unwrap(trace).at(0).native_value()==1); }
      { Vector trace; bool caught=false; try { ' . $key_failure . '(trace); } catch(const std::exception&) { caught=true; }
        require(caught && unwrap(trace).size()==1 && unwrap(trace).at(0).native_value()==2); }
      { Vector trace; bool caught=false; try { ' . $value_failure . '(trace); } catch(const std::exception&) { caught=true; }
        require(caught && unwrap(trace).size()==2 && unwrap(trace).at(0).native_value()==1 && unwrap(trace).at(1).native_value()==2); }
    }';
    $main = str_replace('verify();', 'verify(); cast_proof();', $main);
}
if ($compound) {
    $header .= "\nvoid compound_proof();\n";
    foreach (['compound_work','compound_missing','compound_deleted','compound_map','compound_present','compound_divide'] as $name) {
        $support .= "\n" . '#include "' . CPP_Artifacts::callable_header($name) . "\"\n";
    }
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('compound_work')[0]);
    $missing = CPP_Callables::source_reference(Model::$global_scope->functions_named('compound_missing')[0]);
    $deleted = CPP_Callables::source_reference(Model::$global_scope->functions_named('compound_deleted')[0]);
    $make = CPP_Callables::source_reference(Model::$global_scope->functions_named('compound_map')[0]);
    $present = CPP_Callables::source_reference(Model::$global_scope->functions_named('compound_present')[0]);
    $divide = CPP_Callables::source_reference(Model::$global_scope->functions_named('compound_divide')[0]);
    $support .= 'void compound_proof() {
      begin(); { Vector trace; auto result=' . $function . '(trace); require(result.native_value()==0);
        const long expected[]={1,2,1,4,3}; require(unwrap(trace).size()==5);
        for(int i=0;i<5;++i) require(unwrap(trace).at(i).native_value()==expected[i]);
      } report("compound");
      { Vector trace; bool caught=false; try { ' . $missing . '(trace); } catch(const std::exception&) { caught=true; }
        require(caught && unwrap(trace).size()==1 && unwrap(trace).at(0).native_value()==1); }
      { Vector trace; auto map=' . $make . '(); bool caught=false;
        try { ' . $deleted . '(map,trace); } catch(const std::exception&) { caught=true; }
        require(caught && !' . $present . '(map).native_value() && unwrap(trace).size()==2);
        require(unwrap(trace).at(0).native_value()==1 && unwrap(trace).at(1).native_value()==2); }
      { Vector trace, values; unwrap(values).append(Int{10}); bool caught=false;
        try { ' . $divide . '(values,trace); } catch(const std::exception&) { caught=true; }
        require(caught && unwrap(values).at(0).native_value()==10 && unwrap(trace).size()==2);
        require(unwrap(trace).at(0).native_value()==1 && unwrap(trace).at(1).native_value()==4); }
    }';
    $main = str_replace('verify();', 'verify(); compound_proof();', $main);
}
if ($observations) {
    $header .= "\nvoid observation_proof();\n";
    $support .= "\n" . '#include "' . CPP_Artifacts::callable_header('observation_work') . "\"\n";
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('observation_work')[0]);
    $support .= 'void observation_proof() {
      TrackedVector tracked; unwrap(tracked).append(runtime_type_test::receipt{});
      Parcel parcel; Vector values; unwrap(values).append(Int{1}); unwrap(values).append(Int{2}); unwrap(parcel).value = values;
      begin(); { Vector trace; auto result=' . $function . '(trace,tracked,parcel); require(result.native_value()==0);
        const long expected[]={1,1,2,3}; require(unwrap(trace).size()==4);
        for(int i=0;i<4;++i) require(unwrap(trace).at(i).native_value()==expected[i]);
      } report("observations");
    }';
    $main = str_replace('verify();', 'verify(); observation_proof();', $main);
}
if ($iteration) {
    $header .= "\nvoid iteration_proof();\n";
    foreach (['iteration_work','iteration_tracked','iteration_return'] as $name) {
        $support .= "\n" . '#include "' . CPP_Artifacts::callable_header($name) . "\"\n";
    }
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('iteration_work')[0]);
    $tracked_function = CPP_Callables::source_reference(Model::$global_scope->functions_named('iteration_tracked')[0]);
    $early = CPP_Callables::source_reference(Model::$global_scope->functions_named('iteration_return')[0]);
    $support .= 'void iteration_proof() {
      begin(); { Vector trace; auto result=' . $function . '(trace); require(result.native_value()==92);
        require(unwrap(trace).size()==3); const long expected[]={9,8,3};
        for(int i=0;i<3;++i) require(unwrap(trace).at(i).native_value()==expected[i]);
      } report("iteration");
      TrackedVector values; unwrap(values).append(runtime_type_test::receipt{}); unwrap(values).append(runtime_type_test::receipt{});
      begin(); { auto result=' . $tracked_function . '(values); require(result.native_value()==2);
        require(' . $early . '(values).native_value()==1);
      } report("iteration-copies");
      const int live=live_values; copies_before_throw=1; bool caught=false;
      try { (void)' . $tracked_function . '(values); } catch(const CopyFailure&) { caught=true; }
      copies_before_throw=-1; require(caught && live_values==live);
      require(' . $tracked_function . '(values).native_value()==2);
    }';
    $main = str_replace('verify();', 'verify(); iteration_proof();', $main);
}
if ($mutations) {
    $header .= "\nvoid mutation_proof();\n";
    foreach (['mutation_work','mutation_carrier'] as $name) {
        $support .= "\n" . '#include "' . CPP_Artifacts::callable_header($name) . "\"\n";
    }
    $function = CPP_Callables::source_reference(Model::$global_scope->functions_named('mutation_work')[0]);
    $carrier = CPP_Callables::source_reference(Model::$global_scope->functions_named('mutation_carrier')[0]);
    $support .= 'void mutation_proof() {
      Vector trace; unwrap(trace).reserve(8);
      NullableFloat wrapper; unwrap(wrapper)=scpp::float_t{2.5};
      begin(); { require(' . $function . '(trace).native_value()==0);
        require(unwrap(trace).size()==2 && unwrap(trace).at(0).native_value()==1 && unwrap(trace).at(1).native_value()==2);
        auto old=' . $carrier . '(wrapper);
        require(unwrap(old).require_value("old").native_value()==2.5);
        require(unwrap(wrapper).require_value("new").native_value()==3.5);
      } report("mutations");
      NullableFloat absent; bool caught=false;
      try { (void)' . $carrier . '(absent); } catch(const std::exception&) { caught=true; }
      require(caught && !unwrap(absent).has_value().native_value());
    }';
    $main = str_replace('verify();', 'verify(); mutation_proof();', $main);
}
$provider = '#include "instrument.hpp"' . "\n" . file_get_contents(dirname(__DIR__) . '/runtime_type_support.hpp');
$provider = str_replace('struct receipt { scpp::int_t<> number; };', 'struct receipt { scpp::int_t<> number; Tracked payload{}; };', $provider);
$provider = str_replace('T value;', 'alignas(alignof(T) > ' . $alignment . ' ? alignof(T) : ' . $alignment . ') T value;', $provider);
if ($iteration) { $artifacts->append(new cpp_artifact('proof:protocol', cpp_artifact_kind::header, 'runtime_protocol_support.hpp', file_get_contents(dirname(__DIR__) . '/runtime_protocol_support.hpp'))); }
foreach (['interface.hpp'=>$header, 'runtime_type_support.hpp'=>$provider] as $path=>$text) {
    $artifacts->append(new cpp_artifact('proof:' . $path, cpp_artifact_kind::header, $path, $text));
}
$artifacts->append(new cpp_artifact('proof:support', cpp_artifact_kind::translation_unit, 'support.cpp', $support));
$artifacts->append(new cpp_artifact('proof:entry', cpp_artifact_kind::translation_unit, 'entry.cpp', str_replace('REVISION', (string)($source_arguments ? 0 : $revision), $main)));
foreach (['instrument.hpp', 'instrument.cpp'] as $path) {
    $kind = $path === 'instrument.hpp' ? cpp_artifact_kind::header : cpp_artifact_kind::translation_unit;
    $artifacts->append(new cpp_artifact('proof:' . $path, $kind, $path, file_get_contents(__DIR__ . '/prelower_boundary/' . $path)));
}
$publisher = new Output_Publication($output);
try { $publisher->publish($artifacts, new runtime_cpp_build_spec()); }
finally { $publisher->close(); }
echo "physical bindings and demand replay passed\n";
