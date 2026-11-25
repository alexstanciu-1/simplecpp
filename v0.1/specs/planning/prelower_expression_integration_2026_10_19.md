# Ordinary expression integration: backend contract proposal
Doc Status: planning

Date: 2026-10-19. Inspected baseline: `c75b467a`.
Status: retained contract agreed; bounded ordinary-expression integration implemented.
See [coverage and proof](results/prelower_expressions_2026_10_19/README.md). The findings
below describe the inspected baseline, not the completed checkpoint.

## Finding

The existing semantic facts are sufficient for the next bounded expression slice.
No new AST, preparation pass or template-family registry is needed. The missing
contract is in C++ representation, expression transport and fragment replay.

`CPP_Specializations::representation` still selects the original concrete recipes.
Their expression emitters depend on concrete members such as `native_value` and
`at`. The opaque API proof instead uses original runtime objects in aligned storage.
Changing only the type spelling cannot join these protocols: the stored native
element is not an instance of the application facade.

`cpp_fragment::specializations` retains canonical type requests only. Assembly
replays those into a fresh engine. An operation requested only while emitting a
body would disappear when that body is reused. The current API fixture requests
its operations explicitly; it does not prove ordinary cached-fragment replay.

## Agreed ownership and data contract

Keep the existing owners and extend their backend records locally:

1. `cpp_type` records the physical representation selected by `CPP_Types`: direct
   or in-place opaque. This is a C++ binding choice, not a semantic capability.
   Original runtime spelling remains available through the existing mapping.
2. Expression/declaration emission distinguishes an owned value expression from
   an address into original runtime storage. Use a transient typed transport record
   for statements, value/address expression and canonical type. Do not retain that
   record in syntax or preparation. Reuse existing receiver retention and boundary
   decisions; the record itself neither copies nor owns a source object.
3. Extend fragment specialization requests to retain operation requests as well
   as type requests. A typed backend operation request contains the selected
   callable binding or prepared operation identity, canonical operand/result types,
   and the physical input/result modes needed to regenerate its bridge. It contains
   no expression text, temporary names, AST copy, worker or invocation-local entry
   ID. Do not retain a duplicate semantic analysis of a call.
4. The fragment owns these requests just as it owns existing type/helper requests.
   Replacement/removal drops its requests. Assembly replays the union from live
   fragments into the fresh engine, including unchanged bodies. Stable artifact
   names derive from the complete request, independently of discovery order.
5. `CPP_Specializations` interns requests and returns the existing compact reference.
   Representation/operation workers render support from existing bindings. The
   engine does not branch on vector, hash, integer or provider names. Existing
   publication and native layout caching remain the artifact owners.

The implementation uses `cpp_runtime_operation` for retained requests and
`cpp_runtime_operand` for transient type/expression transport. Existing sequencing
records remain with their owners. No parallel registry or prepared-body cache was added.

## Nested-place rules

The prepared facts deliberately distinguish these two cases:

- `$value = $container[$key]` has `prepared_index_expression`, no prepared place,
  and copied-value semantics. Even an observing caller must not silently turn this
  read into an interior borrow. Construct its required result directly in its
  destination; do not add a second boundary copy.
- `$container[$a][$b] = $value` has `prepared_index_assignment` with a
  `prepared_index_target` and ordered `prepared_index_path_step` records. It is a
  mutation path, not a sequence of copied reads.

Reuse `CPP_Index_Write` sequencing: retain the root as required, evaluate and
snapshot keys with prepared conversions, evaluate the RHS, then validate the path
and leaf and descend mutably. Runtime `index_path_probe`, `index_path_descend` and
`index_store`/`index_append` overloads continue to own storage behavior. An adapter
must not retain an interior pointer while evaluating the RHS. Prepared compound
updates additionally preserve the existing old-value snapshot and re-resolution.

An address into a native element must never be cast to a facade reference. Ordinary
source reference parameters and reference returns must use a consistent transport
contract before such addresses are allowed to cross a generated function boundary.
This does not widen the language's current reference or lifetime rules.

## Bounded implementation sequence and acceptance

1. Implement request retention/replay with cold, no-op, body-edit, call-removal and
   fresh-versus-incremental output equality checks. Preserve the compact engine ref.
2. Integrate owned opaque expressions, literal/conversion construction and readonly
   calls through existing type/call bindings. Include runtime integer wrappers from
   the start. Do not change the boolean option to an incomplete representation
   without updating and validating its existing covered operations.
3. Share indexed-write sequencing and bind native addresses only at the final
   operation. Cover nested reads separately from nested mutation paths. Exercise
   at least two registry-provided families through the same implementation.
4. Compile programs emitted from source, not hand-authored application bridges.
   Compare direct and prelowered allocation, byte, copy, move and destruction counts.
   Include a payload with visible copy counters, nested identity, RHS mutation of
   the root, failure cleanup and a generated source-function boundary.
5. Run O0/O2 and sanitizer checks, followed by the existing layout/native cache
   proof. Report required semantic copies separately from extra boundary work.

Non-goals: new language borrow semantics, cloned/lifted ASTs, heap boxing,
family-specific engine paths, cross-compilation, source-type layout bootstrap,
background workers, broad template coverage claims or new performance ratios.

## Evidence at this checkpoint

A temporary probe through the compiler and shared runner prepared
`count($v[0])` and `$v[0][1] = 7` for `vector<vector<int>>`. It confirmed the read
is non-addressable, the call is `observe_value`, and the write has one path step
and a selected `index_store` operation. This supports reusing preparation; it does
not establish opaque generated-expression correctness or allocation/copy parity.

The existing [layout integration proof](results/prelower_layout_integration_2026_10_19/README.md)
was rerun successfully with `tests/prelower_layout.py` (60.80 seconds; shared runner,
CLI fallback because FPM socket creation was unavailable). Raw results are in
`/tmp/prelower-expression-layout-baseline-20261019/summary.json`. Direct and opaque
paths both measured 4 allocations / 80 bytes / 4 frees for the nested scenario;
the provider scenario measured zero allocations and one required payload copy in
both paths. O0/O2, sanitizer execution and warm/edit/layout-recovery assertions passed.
This remains the native API baseline; the linked expression checkpoint supersedes
the earlier statement that ordinary-expression adoption was outstanding.

Related: [representation audit](prelower_representation_audit_2026_10_19.md),
[implementation plan](cpp_template_prelowering_plan_2026_10_19.md).
