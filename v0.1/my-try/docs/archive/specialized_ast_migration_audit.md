# Specialized AST migration audit
Doc Status: historical

Archived 2026-09-29. This records an earlier design/checkpoint, not current instructions.
See [the documentation index](../README.md) for active guidance.

Comparison baseline: frozen proposal in commit `deee122a`. This audit describes the
implemented migration, not an accepted language specification. The proposal
files remain unchanged. Focused PHP migration checks and the bounded native
portability checkpoints pass. This does not establish full compiler native parity.

## Structure comparison

All 24 concrete syntax classes preserve the proposal's category, typed named fields,
exact kind tag, applicable occurrence/facts traits and inspection iterator choice.
None was replaced with a generic node/payload, common children list or dummy facts.

| Nodes | Owned syntax / applicable state |
| --- | --- |
| `file_node` | Declarations and separate executable body; observed file scope |
| `function_body_node` | Typed statements; observed local scope; canonical body work and syntax change flag |
| `block_node` | Typed statements; no extra lexical scope |
| `named_type_node` | Saved name, occurrence, nullable canonical type facts |
| `punctuation_node`, `comment_node` | Source span; no semantic data |
| `integer_literal_node`, `float_literal_node`, `boolean_literal_node` | Specialized nullable expression facts; bool also saves its value |
| `variable_reference_node` | Saved name, occurrence, specialized nullable reference facts |
| `call_node` | Saved name, typed template arguments and expressions, occurrence and call facts |
| `function_node` | Saved name, template parameters, typed parameters/return/body; owned signature scope; signature facts |
| `parameter_node` | Saved name, type and passing mode; occurrence and parameter facts |
| `binary_expression_node` | Typed operands and existing operator token position; no new semantics |
| `assignment_expression_node` | Assignable target and expression value; assignment facts |
| `expression_statement_node` | One expression |
| `return_node` | Optional expression |
| `variable_declaration_node` | Saved name, required type and optional initializer; occurrence and binding facts |
| `array_type_node` | Element type and extent expression; nullable type facts; current S2S support still rejects |
| `array_literal_node` | Typed expression elements; no new S2S support |
| `index_node` | Typed base/index; no new S2S support |
| `struct_node` | Saved name, typed fields; owned member scope and record facts |
| `field_node` | Saved name/type; occurrence and field facts |
| `field_access_node` | Saved member name and typed base; occurrence and access facts |

Property-free category bases remain as proposed. File declarations cannot be body
statements. PHP Storage does not enforce element types at insertion; typed parser
boundaries and native collection annotations preserve the intended categories.

## Deliberate implementation differences

1. **Common provenance methods:** `set_span`, `start_token` and `end_token`
   are abstract methods on `ast_node`, implemented by the
   concrete traits. Generic cleanup can access provenance without
   requiring base properties or dynamic field access. Span mutation checks uint32
   bounds before changing either endpoint.
2. **Optional occurrence access:** the base has a null `optional_occurrence` and
   rejects attachment. Applicable concrete nodes retain the proposal trait. This
   lets maintenance update only existing occurrences without kind dispatch.
3. **Process owners:** the proposal's illustrative `Work_Entry` class was not added.
   Its checks live at existing `Preparation_Worker` and `CPP_Generator` entry points.
   Cached generation checks active owners too. Cleanup remains able to visit deleted
   nodes. No scheduler or per-node deleted flag was introduced.
4. **Fact ownership:** `prepared_assignment` moved from the proposal's node file to
   `04_analyze/prepare/data/structures.php`. Its shape is unchanged: inherited expression
   type plus a prepared binding. Actual preparation/generation reuse existing
   storage algorithms for explicit declarations and assignments.
5. **Existing containing records:** `parsed_file.root` and `collected_file.root` are
   `file_node`. Duplicate file/function body-work and body-scope fields were removed;
   the body owns work and exposes its observed scope. The existing work identity is
   transferred when replacing a body; unchanged bodies retain their objects.
6. **Production wiring:** proposal-local namespaces/imports/enums became normal
   compiler declarations. Kinds live in `structures_kinds.php`. Maintenance,
   relocation, preparation dispatch and C++ dispatch have process-owned files.
   The old payload factories/accessors and sibling APIs are removed.

The parser completes concrete nodes without returning them through a generic
factory. Retained symbol matching still uses the existing collector, revisions,
scopes and publication locking. Saved names remain independent of token offsets.
The parked LLVM path received mechanical typed-access/assignment adaptations only.
Its separate token-indexed maps and experimental semantics remain parked.

## Behavior and verification

Focused PHP suites passed: `ast`, `ast_invariants`, `structure_access`,
`specialization_dispatch`, `parse_collection`, `model`, `incremental_preparation`,
`preparation_recovery`, `dependency_cleanup`, `combined_sync`, `incremental_cpp`,
`s2s`, `llvm` and `calls`. The latter preserves template/call and generated-program
execution regressions. Checks cover retained identities, body relocation,
independent work, retries, reverse-dependency cleanup, stable output and fresh-build
equivalence. They are not a rigorous exhaustive incremental-correctness proof.

The pre-migration checkpoint is already pushed:
https://github.com/alexstanciu-1/simplecpp/commit/deee122a
No full native my-try compiler build or parity suite was run.

## Approved toolchain extensions

- Direct trait instance fields expand through existing converter field grammar;
  collisions reject, rather than introducing PHP property-merging rules.
- Required zero-argument named-object accessor covariance uses typed public entries
  and return-type-tagged virtual slots, with safe shared-handle upcast bridges.
  STAN checks known class/interface ancestry. This does not implement general
  parameter variance, nullable covariance or arbitrary return covariance.
- Bare `$this` in owning-value positions obtains an alias of the existing shared
  owner through `shared_self`. Member receiver access keeps borrowed `this`.
  Interface diamonds share one virtual `shared_self` base. This adds a weak-owner
  observer/virtual-base layout cost to emitted reference classes; no memory or speed
  improvement is claimed, and a future narrower emission policy needs measurement.
  Unmanaged self and
  self-retention before live ownership fail, rather than manufacturing ownership.
- Explicit qualified object-accessor calls bypass virtual bridges, preserving parent
  call behavior. The bounded native test covers this separately from PHP conversion.

`tests/portability/specialized_ast.py --target-checkout .` covers PHP/native facts,
base/interface dispatch, source trait fields, typed workers and a retained cursor,
plus unrelated-return rejection. `test_shared_self.cpp` checks owner identity,
interface diamonds, lifetime/destruction and unmanaged rejection. The strict STAN
discipline regression also passed. Existing advisory diagnostics are not all solved.

## Approved inspection-iterator extension

The user separately authorized extending converter/runtime/generator/STAN while
preserving lazy typed inspection. The actual production cursor code now converts,
builds and executes natively through `tests/portability/typed_iterators.py`.

The proposal's documentary `storage_children_iterator<T>` becomes one concrete
adapter over `Storage_Cursor<ast_node>`. This fixed native family accepts a
`Storage<Derived>` only when its yielded handle can safely upcast to the requested
base handle. It retains original collection membership without copying children or
converting mutable `Storage<Derived>` into `Storage<Base>`. Cursor aliases share
progress; independent traversal constructs fresh cursors. General user-defined
class templates were not introduced. Composite cursors keep their specialized
classes and named-field state machines.

Supporting changes belong to existing owners:

- Converter accepts literal interface inheritance and nullable interface returns;
  typed cursor annotations use the established fixed-family binding.
- Native `Iterator` is a nominal protocol marker. The generated foreach path uses
  declared `current`/`key` result types; C++ checks the complete typed protocol.
  Value iteration retains the cursor. Membership mutation and by-reference
  iteration are unsupported; inspection accessors are observational.
- Method IR records abstractness explicitly. Empty concrete cursor methods remain
  concrete even when declared in an abstract class.
- Enum switches preserve enum expressions/cases instead of using integer-wrapper
  extraction. The production function cursor exercises this path.
- Nullable shared base handles accept safely convertible derived handles directly.
  This fixes the two-user-conversion boundary without permitting downcasts or
  broadening scalar nullable conversions.
- STAN models cursor methods and declared iterator result types. Build-cache
  fingerprints include the method IR and scanner that preserve these declarations.

The reproducible proof converts all four production model files, then builds and
runs the actual common/function cursor code with small typed node owners. PHP and
native output agree for sparse collections, grammar order, independent cursors,
retained source lifetime, empty traversal, nullable interface returns and rejected
invalid-current/restart operations. Runtime tests separately verify cursor alias
progress, lifetime, sparse keys and compile-time downcast rejection.

Focused compiler Storage typing, portability Storage bindings and strict STAN
discipline regressions pass. Existing advisory STAN diagnostics remain; these are
successful normal STAN-enabled builds, not a zero-diagnostic claim. No complete
native compiler conversion/build/test suite was run.

## Follow-up: appended tokens

The subsequent token-ownership simplification removes `Syntax_Relocation` from
parsing. Reused syntax keeps old indexes into appended token/source storage;
collection retains its occurrences by reused intervals. `Token_Cleanup` performs
normalization after output or before the next scan. Maintenance traversal now lives directly in each specialized node. Workers supply
`enter`, `edge` and `token_index`; `Syntax_Maintenance` and its 24 visitor methods
have been removed. Cleanup workers recurse. Inspection-parent fields/accessors, parser attachment
calls and `Syntax_Attachment` have since been removed: no compiler process needed
them. The frozen proposal retains this historical design; reintroducing parents
requires a concrete consumer. Lexical scope parents are independent and unchanged.
This changes ownership and removes a dispatch layer; no performance gain is claimed
without native measurement.
See [the incremental strategy](incremental_strategy_history.md#appended-token-storage-and-deferred-cleanup--implemented).
