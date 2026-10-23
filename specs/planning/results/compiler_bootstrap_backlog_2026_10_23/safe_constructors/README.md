# Safe PHP constructor promotion batch
Doc Status: planning

Base d041fe624 plus candidate_hashes.json. User authorized further source adaptation
under the existing bounded parent rule, stopping for new semantics/wider redesign.
No converter, generator, runtime or language changes in this batch.

## Audit and adaptations

23 constructors:
- Ten collected syntax owners/references: member owner, field, parameter, variable,
  call reference, field reference, variable reference, constant reference, type
  reference and variable write. Their constructor chain ends at collection-only
  collected_name. No child observations, escaping receiver or callbacks. Required
  syntax links remain private, with their reference annotations retained.
- Anonymous callable identity: promote the syntax link, keeping weak collection
  binding in the body. It neither observes the syntax link nor publishes the receiver.
- Seven canonical type variants: null, boolean, integer, floating, nominal, no-value
  and applied template. Parent canonical_type only validates a positive uint32 ID
  and stores it; no child observation or receiver publication. Its validation remains
  in the parent. Applied-template argument storage keeps its vector annotation.
- Assignment-only File_Preparation, namespace_identity, CPP_Syntax,
  runtime_type_modifier_provider and if_node. Preserve declared field visibility
  and type; enum input is explicit, without inventing a default.

Promoted names follow field names; internal positional call shape stays the same.
No promised external named-argument API is involved. Required properties remain
required, and delayed parser-populated fields were not silently made nullable.

## Validation

Existing type catalog, namespace, collected-role, callable, standalone preparation
and retained-recovery proofs pass (each 0.50–1.13 s). Constructor portability fixture
passes PHP, legacy and both v0.2 output modes, including parent construction and
negative-order controls; existing constructor failure proofs also pass. Native
success tasks: legacy 17.99 s, ordinary 19.42 s, concrete 19.21 s. Failure tasks:
legacy 25.26 s, ordinary 23.53 s, concrete 22.80 s. These are task/build timings,
not runtime performance measurements. Shared runner used its normal CLI fallback.

## Boundary requiring discussion

Callable_Syntax::initialize_callable_syntax() allocates signature_syntax and
owned_signature_scope, then configures the scope. Both named callable and closure
constructors use this helper. These are trait-owned required properties created in
constructor bodies, not existing constructor inputs that can simply be promoted.
The current staged-field converter does not cover trait/inheritance ownership.
Do not auto-hoist helper bodies or weaken required contracts. A source factory/input
redesign would touch both owning classes and creation sites; a converter extension
needs a separately bounded contract. Stop before making either change. Remaining
safe opportunities elsewhere are not claimed exhausted by this focused batch.

## Measured bootstrap

86.60 s wall / 351180 KiB peak RSS (342.95 MiB) / 320 MiB PHP heap.
Preparation still fails; 11 distinct diagnostic messages removed and none added
relative to parent_promotion. The former first syntax_node diagnostic is removed;
first remaining message is required signature_syntax initialization. This is a
PHP-hosted preparation attempt, not a completed native compiler bootstrap.
See diagnostic_delta.json. The 92.25 s overall task also includes conversion/setup.
