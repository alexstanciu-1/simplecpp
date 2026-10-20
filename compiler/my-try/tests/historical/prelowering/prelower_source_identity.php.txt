<?php
namespace scpp\compiler;
// Native inspection of compiler-generated factory results; no replacement runtime algorithms.
$proof = "#include \"instrument.hpp\"\n#include <memory>\n#include <type_traits>\n#include <utility>\n";
$engine = $opaque ? new CPP_Specializations(true) : null;
foreach (['shared', 'unique', 'value'] as $kind) {
    $function = Model::$global_scope->functions_named('make_' . $kind)[0];
    $type = $function->signature()->return_type;
    $native = CPP_Types::representation($type);
    $surface = CPP_Types::representation($type, new cpp_generation_context($engine));
    $proof .= CPP_Artifacts::includes($native->headers, '') . CPP_Artifacts::includes($surface->headers, '');
    $proof .= '#include "' . CPP_Artifacts::callable_header('make_' . $kind) . '"' . "\n";
    $proof .= 'using Native_' . $kind . ' = ' . $native->spelling . '; using Surface_' . $kind . ' = ' . $surface->spelling . ";\n";
    $proof .= 'Native_' . $kind . '& inspect(Surface_' . $kind . '& input) { return '
        . ($opaque ? '*std::launder(static_cast<Native_' . $kind . '*>(input.address()))' : 'input') . "; }\n";
}
$proof .= <<<'CPP'
static_assert(!std::is_copy_constructible_v<Surface_unique>);
static_assert(std::is_copy_constructible_v<Surface_shared>);
static_assert(std::is_copy_constructible_v<Surface_value>);
void source_identity_proof() {
 require(live_values==0);
 begin();
 {
  auto owner = function_makeU_shared();
  auto alias = owner;
  require(inspect(owner).get()==inspect(alias).get());
  require(live_values==1 && counts.copied==0);
  auto unique = function_makeU_unique();
  auto* identity = inspect(unique).get();
  auto moved = std::move(unique);
  require(inspect(unique).get()==nullptr && inspect(moved).get()==identity);
  require(live_values==2 && counts.copied==0);
  auto value = function_makeU_value();
  auto copy = value;
  require(&inspect(value).get()!=&inspect(copy).get());
  inspect(copy)->field_amount = 91;
  require(inspect(value)->field_amount.native_value()==7);
 }
 require(live_values==0);
 report("source-identities");
}
CPP;
foreach (['make_shared', 'make_unique', 'make_value'] as $name) {
    $proof = str_replace('function_' . str_replace('_', 'U_', $name), CPP_Generator::source_name('function', $name), $proof);
}
$artifacts->append(new cpp_artifact('proof:source-identities', cpp_artifact_kind::translation_unit, 'source_identity.cpp', $proof));
