<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Host integration fixture: production preparation, type bindings, engine and publisher.
[$script, $source, $output, $mode] = $argv;
$revision = (int)($argv[4] ?? 0);
$alignment = (int)($argv[5] ?? 8);
$composed = $mode === 'composed';
$source_arguments = ($argv[6] ?? '') === 'arguments';
$fields = (($argv[6] ?? '') === 'fields') || $source_arguments;
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
    $snapshot = static function () { $r=[]; foreach(Model::$cpp_files as $a) {$r[$a->file_name]=$a->text;} return $r; };
    $before = $snapshot();
    $fragments=[]; foreach(Model::$cpp_output_program->fragments as $owner) {$fragments[] = Model::$cpp_output_program->fragments[$owner];}
    $compiler->cpp();
    if ($snapshot() !== $before) { throw new \LogicException('Concrete source fragment replay changed output'); }
    $index=0; foreach(Model::$cpp_output_program->fragments as $owner) {
        if (Model::$cpp_output_program->fragments[$owner] !== $fragments[$index++]) { throw new \LogicException('No-op regenerated a source fragment'); }
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
$engine = new CPP_Specializations();
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
    $engine->append_artifacts($artifacts);
    // Request order and repeated requests must not change generated bytes or semantic state.
    $other = new CPP_Specializations();
    foreach (array_reverse($types) as $type) { $other->request_composed($type); $other->request_composed($type); }
    $replayed = new Storage();
    if ($source_arguments) {
        foreach (Model::$cpp_files as $artifact) {
            if ($artifact->kind() === cpp_artifact_kind::header && !str_starts_with($artifact->file_name, 'specializations/')) { $replayed->append($artifact); }
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
