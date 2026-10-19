<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Host integration fixture: production preparation, type bindings, engine and publisher.
[$script, $source, $output, $mode] = $argv;
$revision = (int)($argv[4] ?? 0);
$alignment = (int)($argv[5] ?? 8);
$composed = $mode === 'composed';
$source_arguments = ($argv[6] ?? '') === 'arguments';
$initializers = ($argv[6] ?? '') === 'initializers';
$indexing = $initializers || ($argv[6] ?? '') === 'indexing';
$members = $indexing || ($argv[6] ?? '') === 'members';
$fields = $members || (($argv[6] ?? '') === 'fields') || $source_arguments;
$metadata = new runtime_catalog_input();
foreach (Model::$runtime_metadata->modules as $module) { $metadata->modules->add($module->name, $module); }
$metadata->modules->add('type_proof', Runtime_Catalog_Reader::decode(file_get_contents(dirname(__DIR__) . '/runtime_types.json'), 'type_proof.json'));
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
if ($source_arguments) {
    $program .= '
class MyClass { public vector<int> $amounts; public function duplicate(): vector<int> { return $this->amounts; } }
function source_demand(vector<Record> &$rows, parcel<Record> &$box, vector<MyClass> &$owners): void {}';
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
    $mapping = $composed ? CPP_Opaque_Representation::representation($type, $engine, true) : CPP_Types::representation($type);
    $header .= CPP_Artifacts::includes($mapping->headers, '') . 'using ' . $name . ' = ' . $mapping->spelling . ";\n";
    $native = $composed ? CPP_Types::implementation_representation($type, $engine) : CPP_Types::representation($type);
    $support .= CPP_Artifacts::includes($native->headers, '') . 'using Native_' . $name . ' = ' . $native->spelling . ";\n";
    foreach (['', 'const '] as $qualifier) {
        $expression = $composed ? '*std::launder(static_cast<' . $qualifier . 'Native_' . $name . '*>(value.address()))' : 'value';
        $support .= $qualifier . 'Native_' . $name . '& unwrap(' . $qualifier . $name . '& value) { return ' . $expression . "; }\n";
    }
}
if ($composed) {
    if ($source_arguments) {
        foreach (Model::$cpp_files as $artifact) {
            if ($artifact->kind() === cpp_artifact_kind::header && !str_starts_with($artifact->file_name, 'specializations/')) { $artifacts->append($artifact); }
        }
    }
    if ($members) {
        foreach (Model::$cpp_output_program->fragments as $owner) {
            foreach (Model::$cpp_output_program->fragments[$owner]->runtime_operations as $request) { $engine->request_operation($request); }
        }
    }
    $engine->append_artifacts($artifacts);
    // Request order and repeated requests must not change generated bytes or semantic state.
    $other = new CPP_Specializations(false, $composed);
    foreach (array_reverse($types) as $type) { $other->request_composed($type); $other->request_composed($type); }
    $replayed = new Storage();
    if ($source_arguments) {
        foreach (Model::$cpp_files as $artifact) {
            if ($artifact->kind() === cpp_artifact_kind::header && !str_starts_with($artifact->file_name, 'specializations/')) { $replayed->append($artifact); }
        }
    }
    if ($members) {
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
    $support .= 'Handle make_handle() { return ' . ($composed
        ? 'Handle(Handle::operation_result{}, +[](void* destination, const void*) { ::new(destination) Native_Handle(std::make_shared<Payload>()); }, nullptr)'
        : 'Handle(std::make_shared<Payload>())') . "; }\n";
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
        unwrap(owners).append(owner);
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
$provider = '#include "instrument.hpp"' . "\n" . file_get_contents(dirname(__DIR__) . '/runtime_type_support.hpp');
$provider = str_replace('struct receipt { scpp::int_t<> number; };', 'struct receipt { scpp::int_t<> number; Tracked payload{}; };', $provider);
$provider = str_replace('T value;', 'alignas(alignof(T) > ' . $alignment . ' ? alignof(T) : ' . $alignment . ') T value;', $provider);
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
