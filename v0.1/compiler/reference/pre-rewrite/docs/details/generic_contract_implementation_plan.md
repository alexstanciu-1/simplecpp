# Default generic contract: bounded implementation
Doc Status: supporting

Approved and implemented on 2026-09-20. The user selected the bounded migration:
preserve storage/ownership proofs with concrete element types and defer the
growing list's generic migration. The [semantic decision](generic_type_contract.md)
owns the language extension; this document records implementation and proof scope.

## Owners and flow

| Owner | Responsibility |
|---|---|
| [type_model/generic_contracts.php](../../src/04_analyze/type_model/generic_contracts.php) | `generic_contract::copyable_value` (declared in `data/generic.php`) and `Generic_Contracts::missing()` query accepted lifecycle facts. No cached eligibility flag or type-name blacklist. |
| [Formal bindings](../../src/04_analyze/resolve_symbols/data/declarations.php) | Each type slot retains the default contract; value slots retain their literal-integer rules. Identity remains definition ID plus ordered position. |
| [check_templates](../../src/04_analyze/check_templates/calls.md) | Check definition permissions before specialization, including unused definitions and inherited-parameter methods. |
| [Concrete_Preparation](../../src/04_analyze/resolve_types/main_prepare_concrete.php) | Run the definition-check lifecycle at initialization, before literal/application/record/member work. Retain accepted checks in `Instance_Set`. |
| [Application_Worker](../../src/04_analyze/instantiate/applications.php) / [Instance_Join](../../src/04_analyze/instantiate/join.php) | Require accepted definition checks and validate every concrete type argument against the complete baseline after prerequisite definitions become ready. |
| [Body_Worker](../../src/04_analyze/check_bodies/body.php) | Require the current definition permission result before checking a concrete template body. Existing owners still select concrete operations, lifetime effects and ABI forms. |

## Symbolic permission checks

`Terms` reads fixed bound declarations and signatures on demand. It never follows
a call into the callee body. Each template definition has its own selected task;
this avoids preparing a second signature dataset or expanding call paths.
Private symbolic terms retain named declarations, parameter identities and ordered
family arguments. They have no canonical concrete ID or invented layout.

`Template_Worker` traverses statements and expressions iteratively. Both ordinary
branches must respect the same permissions; it does not enumerate execution
paths. Copies, assignments and forwarded call results preserve symbolic identity.
Distinct formal slots stay distinct even when a later call supplies the same type.
Known source families may expose their own declared fields/methods; bare `T` may
not acquire members from concrete substitution.

This is permission checking, not a second complete body/type checker. Concrete
operation validity and supported value/reference ABIs remain downstream. Unsupported
symbolic statements/expressions fail explicitly, including compile-time branches;
constant evaluation is not implemented. Symbolic member checking now covers source
declarations and metadata-declared provider-family members through their shared
contracts. Additional structural interfaces and capability syntax remain deferred.

## Selection, private work and acceptance

`Template_Checker` uses the common init/run/finalize/result lifecycle. Selection
checks full-rebuild or stale owner, binding, referenced-declaration and catalog
snapshots. Each worker reads fixed inputs and owns its symbolic scratch/result.
`Template_Join` validates selected task provenance, result freshness, duplicates
and coverage, then reconciles current membership and unchanged results.

Only accepted proof/dependency rows are retained; no symbolic AST clone, expanded
call tree or concrete body is stored by this stage. `Instance_View` exposes the
accepted set to workers and joins. A supported ordinary body edit selects no
unchanged definition work and retains result identities. Template definition and
schema changes keep the existing full-rebuild fallback. Actual threading and new
incremental/recovery categories are outside this slice.

## Migrated proof sources

- Fixed-array/list proofs use concrete element types with templated integer
  capacities. `T data[N]` is a negative diagnostic: default initialization is not
  promised by the default contract.
- Growing-list allocation, append, copying, assignment, self-assignment, alias and
  incremental proofs use concrete element records. Test code expands common fixture
  text into these ordinary declarations; the compiler receives no exemption.
- Custom lifecycle, owning fields and typed-storage helpers likewise use concrete
  types where their operations require capabilities beyond the baseline.
- Generic recursion uses an independent condition. Integer comparison uses a
  concrete integer parameter plus a literal value parameter. Neither grants
  arithmetic or truth conversion to bare `T`.
- Positive generic proofs now exercise genuine baseline operations: copying,
  assignment, compatible forwarding, const record borrowing and declared family
  member access. One- and two-type parameters use the same implementation.

The original growing storage subset required cleanup-free elements.
[Managed slot lifecycle](managed_element_storage_plan.md) has since extended it,
but indexed compiler-tracked allocation-owner elements remain unsupported. Dependent
uses still reject even when a favorable concrete argument would pass. Restoring the
generic growing list requires resolving that baseline compatibility gap; concrete
specialization never grants extra definition-level permissions.

## Evidence and limits

[Focused generic proof](../../tests/04_analyze/check_templates/generic_contracts.php):

- Native scalar and record copies, assignment and forwarding; multiple concrete
  instances share definition checks. One ordinary body edit changes native output.
- Unauthorized members/operators/conditions/default construction rejected without
  instantiation, including after copying/forwarding and with favorable concrete types.
- Independent formal slots remain distinct. Const writes and mutable bare-T
  references are rejected.
- Missing copy/assignment/lifetime rejected using imported and source-composed
  contracts, even when a body only borrows or does not use the parameter.
- Reverse worker completion, incomplete/duplicate/altered/stale join rejection and
  retained-snapshot purity. Selected counts prove definition reuse.

[Current full-suite checkpoint](../planning/compiler_foundations.md#current-status-and-next-focus)
includes the migrated native storage/ownership proofs. Run fixtures with ten workers;
timings are correctness-run timings, not compiler-performance measurements.

Deferred: user-written capability syntax, move-only generics, `new T`, reflection
intrinsics, general constant evaluation, managed dynamic elements, generic fixed
arrays, general aggregate value ABIs and borrowed returns. Existing supported
concrete operations remain available. No legacy unchecked-template mode was added.
