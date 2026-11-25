# CONVERT-01 typed empty construction/default reuse audit
Doc Status: planning

2026-10-23. Source inspection only, no implementation, native execution or bootstrap.
Accepted behavior: explicit portable PHP type comments; metadata-driven typed []
construction and parameter defaults; no per-container type whitelist or current
opt-out flag. This audit refines implementation scope, not the accepted behavior.

## Existing machinery

1. **Syntax/target selection:** Data_Construction_Preparation consumes one bracket/
   brace AST, gets a concrete target from expected type or explicit type, and records
   empty_brackets distinctly from keyed input. No new parser or literal family needed.
2. **Registered initialization:** Initialization_Preparation selects positional
   initialization first for [], then keyed when absent, substitutes concrete generic
   arguments and publishes a binding. Registered vector/hash definitions already
   provide initialization metadata and C++ adapters. Cached selection rechecks input
   capability requirements. Unknown/unsupported targets already have diagnostics.
3. **Empty emission:** CPP_Data_Construction::sequence directly invokes the selected
   factory with an empty input sequence. generate uses the same prepared binding for
   ordinary output or CPP_Runtime_Operations::initialize under prelowering.
4. **Construction defaults already exist:** Property_Defaults prepares constructions
   with constant leaves; prepared_construction_property_default retains the prepared
   node and conversion. prepared_property_parameter_default already embeds that
   default for synthesized constructor inputs. Constructor_Preparation creates it;
   Parameter_Defaults::same and CPP_Declarations::parameter_default handle it.
   Earlier estimates implying that no construction-default fact exists were too broad.
5. **Per-call evaluation:** CPP_Declarations already materializes omitted fixed
   arguments in call-local default_argument temporaries, then passes/moves them through
   existing call transport. Thus fresh empty construction per omission has a reusable
   execution point; no singleton default or new default-evaluation scheduler is needed.
6. **Converter type intent:** methodSignature reads adjacent container annotations
   before parsing defaults. sequence recognizes standalone annotated empty expressions
   but emits new Type(), which is unsuitable for v0.2 vector construction. Structural
   annotation parsing and missing-comment diagnostics can be reused.
7. **Proof infrastructure:** initialization/data-initialization/property-default,
   handle/nullable-default and prelowered initializer fixtures already exist. They
   provide extension points, not proof of explicit container parameter defaults.

## Missing work

### Registered container empty construction

Existing vector/hash typed [] is not a missing language parser feature. General
registered containers need a truthful empty-construction route in the same owning
initialization model. Example: compiler.json Storage families declare make/default
construction and some indexing/iteration protocols but no initialization recipes.
Do not infer container status from indexing/iteration alone (cursors/views are not
necessarily constructible containers), and do not grant [] to every default-constructible
class. Resolve the metadata role/binding gap through the existing registry/provider
owners, using a registered empty factory or appropriate initialization adapter.

Existing sequence initialization requires an admitted input sequence/element domain;
empty construction must not accidentally require default-constructing an element.
If a provider's current sequence-input capability constraint is too strong even for
its empty factory, report that concrete model gap rather than erase capability checks.
Do not silently add the deferred opt-out flag or hard-code Storage/vector names.
Source-defined general initialization interfaces remain separately deferred; the
accepted metadata-driven route is not approval to implement them all.

### Authored parameter defaults

Parameter_Defaults currently accepts scalar/enum/null forms and rejects non-scalar
container defaults. Reuse/factor the existing construction-default preparation and
fact/emission path for explicitly typed empty container defaults. Preserve the target
from the parameter after generic substitution. A small rename/factor may clarify
ownership because the current reusable fact is named for properties; do not duplicate
it solely to avoid touching its true owner.

Initial authored-default scope is [] for admitted containers. Reusing a more general
property default representation must not accidentally admit arbitrary calls, nonempty
expressions, all data-record defaults, or container reference defaults. Existing
reference/default-copy and ownership rules are separate admission constraints.
Do not bypass the registered factory by emitting Type{} merely because it compiles.

Use existing omitted-argument call-local construction, retaining caller evaluation
order and no construction when an explicit argument is supplied. Constructors,
methods, ordinary calls and any shared callable/virtual dispatch consumers must use
the common default owner. Preserve existing default selection semantics; PHP/native
override-default differences are not changed by allowing an empty value.

Runtime metadata's own callable-default schema remains scalar-specific and generic
runtime pattern slots store literal defaults. Source parameter support does not prove
provider-authored defaults. Identify this as an additional metadata-schema integration
slice if required by a concrete provider; do not hide it inside the source fix or
claim broad provider-default coverage. The requested source parameter example is
Native_CPP_Commands::link(... vector<string> provider_options = []).

### Converter and cross-route compatibility

Accept [] after a locally available explicit container annotation in ordinary and
promoted parameter grammars where otherwise admitted. Diagnose missing type intent;
do not infer remote expression types or repeat the target comment unnecessarily.
Standalone annotated [] must use supported target-directed construction. v0.1 has
its own established typed empty lowering; do not assume v0.2 Type::[] notation is
accepted by the historical route. Prefer common admitted output; if necessary use
existing route-specific lowering while preserving behavior and evaluation count.
Cross-route proof is required before declaring the converter gap closed.

## Overlap

- INIT-01 shares promoted-parameter parsing and constructor initialization; factor
  common default handling, do not count a second constructor/default implementation.
- NULL-02 handles nullable null defaults and optional payload capabilities. Present []
  is not null; generic empty-container construction must not depend on truthiness.
- NULL-01 supplies required/optional frontend contracts, not this default scheduler.
- Registry audit classifies provider roles/adapters and any remaining family gates;
  CONVERT-01 repairs its affected construction consumers, not the entire CH15 debt.
- Ordinary/prelowered wrappers and retained defaults already have owners. Add proofs
  instead of a separate converter-specific compiler execution path.

## Revised workload and sequence

Revise **6–12x to 4–8x**, conditional on reusing the existing construction/default
facts and metadata initialization model. This includes the source default/converter
slice and bounded registered-container integration, not arbitrary provider-default
schemas or all source-written factories. Sub-slices are overlapping estimates:

1. Registered empty-construction role/binding audit and bounded integration: 1–2x.
2. Explicit parameter-default admission using existing default facts/call temporaries:
   1–2x, with ordinary/prelowered and retained integration.
3. Converter ordinary/promoted/default/expression grammar plus cross-route proofs:
   2–4x, sharing INIT-01 work where appropriate.

Recommended first witness: existing annotated provider_options default in link(),
followed by a generic registered container that is not vector/hash. Prove repeated
omitted calls do not share mutable default state; explicit arguments suppress default
construction; list-only and keyed-only metadata select valid empty factories; missing
type and unsupported target reject clearly; generic specialization/default changes
invalidate callers and regenerate ordinary/prelowered artifacts. Include retained
source restoration and PHP/v0.1/v0.2 execution proofs. Do not duplicate implementation
with tests that merely inspect its spelling.

Escalate if empty construction cannot be represented in the existing factory owner,
if historical-route compatibility requires new language semantics, or if default
facts cannot safely retain construction dependencies. No such wide redesign is
established by this audit. No implementation authorized.

## Execution follow-up

Implementation was authorized after this audit. Typed parameter defaults and
explicitly annotated empty expressions now have PHP/current legacy/v0.2 ordinary
and prelowered proofs. Converter output targets are explicit, defaulting to v0.1;
v0.2 uses Type::[] for standalone empties. Missing type intent rejects, while a
local declaration's annotation covers directly nested/grouped literals. The actual
compiler envelope converts after source-only annotation/count adaptations.

Registered container integration beyond existing sequence recipes remains pending,
as do broader retained/default effects. The registry audit is not closed by the
existing generic tray<int> factory proof. See the execution ledger for exact
checkpoints and the known unrelated baseline test drift.
