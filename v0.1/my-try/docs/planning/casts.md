# Cast and conversion preparation
Doc Status: planning

Discussion started: 2026-10-01

## Purpose and authority

This document records the agreed implementation direction for casts and contextual
conversions in `compiler/my-try`. It is an implementation plan, not a user-visible
language specification. Normative conversion behavior remains owned by the root
specifications and the runtime contracts they select.

The active delivery path is the shared frontend plus C++ S2S. LLVM and legacy STAN
remain parked. Native compilation of the compiler is a separate, explicitly
requested validation step.

## Core boundary

The compiler owns:

- recognizing explicit cast syntax;
- resolving source and requested target types to canonical type uses;
- deciding whether the requested conversion is legal in its exact context;
- deciding whether the conversion is identity or requires a runtime/backend
  operation;
- publishing the conversion result type and selected operation for consumers.

The runtime owns conversion behavior, including parsing, range checks, coercion,
failure behavior and exceptions. The C++ backend consumes a prepared decision and
renders the selected helper and target representation. It must not independently
rediscover conversion legality from source tokens, AST subclasses or a second
source/destination matrix.

## Syntax versus compiler-required conversions

An explicit source cast such as `(int)$value` is real source syntax. Its AST shape
retains the target type syntax, operand and source span. There is one general
explicit-cast expression shape, not one node per target family or source/target
combination.

Conversions required by assignments, arguments, returns, operators and conditions
must not be represented by injecting synthetic cast nodes into the AST. They are
semantic relationships between an expression and its consumer. Their decisions are
attached to the existing prepared fact owned by that consumer.

Examples of decision ownership:

| Source construct | Prepared owner of the conversion decision |
| --- | --- |
| `(int)$value` | explicit-cast expression |
| typed initializer or reassignment | declaration/assignment binding |
| by-value call argument | resolved argument slot |
| typed return | return statement |
| operator operand | prepared operator expression after signature selection |
| condition | prepared condition |

Reference arguments do not request value conversion. They continue to require
addressable storage with the exact compatible storage type.

## Decision timing

`Conversion_Preparation::decide()` is called during semantic preparation by the
consumer that establishes the target and context. It is not called during parsing,
collection, type registration or C++ generation, and it is not a separate global
tree-rewriting pass.

The required order is:

```text
prepare the source expression
-> obtain its canonical source type use
-> resolve the consumer's canonical target type use
-> call Conversion_Preparation::decide(source, target, context)
-> attach the decision to the prepared consumer
-> expose the decision's result type
-> let the backend render the retained decision
```

Local references are therefore resolved before conversion selection. Exact
canonical identity is checked before family rules, so aliases such as `byte` and
`uint8` and repeated interned applications do not acquire conversions merely because
their source spellings differ.

Explicit nested casts prepare from the inside outward. Operators first prepare
their operands and select an applicable operator signature; only then can each
operand request conversion to the signature's expected type. A condition similarly
requests conversion only after its expression type is known.

## Semantic owner and file organization

Conversion algorithms belong directly under analysis rather than adding another
deep preparation subtree:

```text
04_analyze/conversions/
|- model.php
|- preparation.php
|- booleans.php
|- integers.php
|- floating_points.php
`- strings.php
```

The intended responsibilities are:

- `model.php`: the small backend-neutral context, operation and decision data;
- `preparation.php`: the public decision entrypoint, exact-identity handling,
  target-family routing and common rejection diagnostics;
- family files: rules for conversions whose requested result belongs to that
  target family.

Rules are organized by target family. Each target-family owner inspects the source
family and conversion context. There is no source-family by target-family file
matrix.

`Conversion_Preparation::decide()` has the conceptual signature:

```php
public static function decide(
    canonical_type_use $source,
    canonical_type_use $target,
    conversion_context $context,
): conversion_decision;
```

The initial decision records the source, target/result, context and selected
backend-neutral operation. The exact compact PHP representation will be reviewed
against the portable native compiler before implementation. It must remain a
semantic fact rather than containing C++ spelling.

Initial operation distinctions are expected to include identity, explicit runtime
cast, required typed-boundary conversion and condition conversion. Only operations
required by the first implemented slice should be introduced; later distinctions
replace or extend the small operation vocabulary without changing AST shape.

## Hard-coded and declarative rules

The first implementation is deliberately hard-coded. Later versions may combine
algorithmic rules with registered capabilities or configuration, but configuration
must declare permission/capability rather than implement runtime conversion behavior.

Integer-family conversion must remain algorithmic. It uses canonical integer facts
such as bit width and signedness to classify identity, widening, narrowing and
signed/unsigned boundaries. It must not enumerate every concrete integer pair in a
configuration matrix.

The same principle applies to floating-point formats and precision where the rule
is derived from semantic type facts. Declarative configuration is more appropriate
for nominal/runtime types that advertise a conversion capability. A runtime helper's
existence does not by itself grant a source-language conversion in every context.

## Initial bounded cast slice

The proposed first explicit-cast slice contains the four catalog entries:

- `CAST-INT-001`;
- `CAST-FLOAT-001`;
- `CAST-BOOL-001`;
- `CAST-STRING-001`.

The intended C++ form is the configured runtime helper:

```cpp
cast<target_type>(operand)
```

The legacy S2S currently emits this common form for all four explicit scalar casts.
The imported catalog still contains stale `static_cast` examples for float and bool
while its general rules require `cast<T>`; the v0.2 decisions must remove that
internal inconsistency rather than copying it.

The runtime already contains configured scalar conversions and owns their concrete
behavior. Frontend permission must nevertheless be explicit: unsupported pairs are
rejected during preparation rather than accepted through arbitrary C++ construction.

Object casts, `mixed`, nullable extraction, pointer/value wrappers, containers and
record conversions are pressure tests for the model, not part of this first slice.

## Interaction with existing code

Before Stage 1, `Type_Preparation::require_assignable()` admitted exact matches and
integer-family assignment compatibility while `CPP_Declarations::value()`
rediscovered integer destinations and emitted a `static_cast` directly. That split
between semantic selection and lowering has now been removed for the migrated
boundaries.

The staged implementation replaced that split with one prepared decision consumed
by the backend: assignments migrated first, followed by arguments and returns,
before explicit source casts were added. No second compatibility system remains in
those paths.

## Implementation steps

1. **Finalize the bounded contract.**
   - Confirm initial source and target families.
   - Confirm same-type explicit-cast emission policy.
   - Confirm whether existing implicit integer boundaries migrate in the same pass.
   - Record the v0.2 decisions for the four scalar cast catalog entries.

2. **Add the conversion model and routing owner.**
   - Create `04_analyze/conversions/` with the agreed shallow layout.
   - Define the minimal context, operation and `conversion_decision` representation.
   - Implement exact canonical identity before family dispatch.
   - Implement explicit rejection rather than a permissive fallback.

3. **Implement target-family policies.**
   - Add boolean, integer, floating-point and string decision owners.
   - Derive integer decisions from width and signedness rather than pair tables.
   - Keep runtime behavior out of these files.
   - Add only the contexts exercised by the bounded slice and any agreed migration.

4. **Add explicit-cast syntax and preparation.**
   - Parse one general explicit-cast expression containing target type syntax and
     an operand.
   - Preserve correct precedence and nested-cast structure.
   - Resolve the target through the existing type registry.
   - Prepare the operand first, call `decide()`, attach the decision and publish its
     result type.

5. **Attach contextual decisions at agreed boundaries.**
   - Store decisions on existing prepared declaration/assignment, call-argument and
     return owners if those paths are included in this pass.
   - Do not insert synthetic AST nodes.
   - Preserve the stricter exact-storage rule for reference arguments.

6. **Make C++ emission decision-driven.**
   - Render identity without a conversion wrapper according to the agreed policy.
   - Render explicit scalar conversion through `cast<T>(operand)`.
   - Obtain target spelling and headers from the existing C++ type binding.
   - Remove any migrated backend-side type-family decision branches.

7. **Add focused proofs.**
   - Cover each scalar target and representative legal source families.
   - Cover same-canonical-type and alias identity.
   - Cover nested casts, precedence, variable/call/field operands and single
     evaluation of effectful operands.
   - Cover rejected unsupported pairs and invalid targets.
   - Preserve runtime-owned tests for invalid numeric strings, range overflow,
     truncation and formatting rather than duplicating those algorithms in the
     compiler.
   - If contextual boundaries migrate, prove assignment, reassignment, argument and
     return decisions, including reference rejection.

8. **Consolidate and record evidence.**
   - Remove the replaced compatibility/emission branches within the agreed boundary.
   - Update the four catalog progress rows and their v0.2 decision sections.
   - Run focused PHP/frontend and C++ S2S tests.
   - Run broad or native-compiler validation only when explicitly requested.

## Non-goals

- No source/target combination classes or per-target cast AST subclasses.
- No compiler-injected synthetic cast AST nodes.
- No configurable integer-pair matrix.
- No runtime conversion algorithms in the compiler.
- No implicit operator-overload framework merely to prove scalar casts.
- No `mixed`, nullable, ownership-wrapper, container, record or object cast support
  in the first scalar slice.
- No LLVM work or legacy STAN resumption.
- No native compiler validation without an explicit request.

## Agreed implementation decisions

- Existing integer assignment, argument and return conversions migrate onto
  `conversion_decision` in staged slices within this implementation.
- A same-type explicit cast retains its syntax and identity decision but emits only
  its operand.
- The parser accepts the general `(type)expression` shape and resolves the target
  through the type registry. The initial semantic policies cover registered scalar
  targets rather than embedding four cast names in parser control flow.
- The scalar family files explicitly admit the boolean/integer/floating/string pairs
  supported by the runtime cast contract; runtime availability is evidence, not an
  implicit grant for unrelated contexts.
- The floating-point rule owner is named `floating_points.php`; “decimal” remains
  available for a future exact decimal family.
- Every typed consumer calls `Conversion_Preparation::decide()`, including when the
  types are equal. Exact canonical identity is decided centrally. An inferred first
  assignment has no pre-existing expected type and therefore makes no request.

## Implementation progress

### Stage 1 — assignment decisions

Implemented:

- the minimal decision model and central identity/target-family router;
- the integer target-family owner preserving the existing assignment behavior;
- decisions attached to typed declarations, reassignments and field writes;
- C++ storage emission consuming the retained decision rather than comparing types;
- focused identity, integer conversion, alias and rejection proofs.

At the end of this checkpoint, calls and returns deliberately retained the prior
compatibility and C++ rendering path. Stage 2 below removes that temporary boundary.
Explicit-cast syntax and boolean, floating-point and string policies had not started.

### Stage 2 — argument and return decisions

Implemented:

- one aligned prepared boundary per call argument;
- conversion decisions for every by-value argument after callable resolution;
- reference arguments retaining their exact-storage rule and no conversion;
- prepared return facts carrying decisions for typed value returns;
- C++ call and return emission consuming those retained decisions;
- removal of the former shared `require_assignable()` check and backend-side
  integer type inspection.

All existing integer assignment, argument and return boundaries now use the central
conversion owner. Explicit-cast syntax and the remaining scalar-family policies are
the next stage.

### Stage 3 — explicit scalar casts

Implemented:

- one source-written cast expression for the general `(type)expression` form;
- target lookup through the canonical type registry rather than parser-owned cast
  keyword cases;
- boolean, integer, floating-point and string target-family policies;
- exact canonical identity emitting the operand unchanged;
- non-identity scalar casts retaining `explicit_runtime_cast` and lowering through
  `scpp::cast<target>(operand)` with the required runtime header;
- nested casts, registered fixed-width integer targets, effectful operands and
  operator-precedence proofs;
- explicit rejection of nominal-to-scalar pairs outside the bounded contract.

The existing expression grammar does not yet support parenthesized grouping.
Consequently `(int)2.5 + 1` proves that a cast binds before addition, while
`(int)($a + $b)` remains deferred with grouped expressions rather than being
silently treated as part of cast syntax.

### Stage 4 — proof completion

Implemented:

- explicit canonical alias identity through `uint8` to `byte`, proving that source
  spelling does not create a runtime conversion;
- a field-access operand using the ordinary prepared field facts before the cast
  decision;
- exact generated-C++ assertions for both the field runtime cast and the unwrapped
  alias identity path;
- integer-target admission aligned with the current runtime contract: canonical
  `int` accepts every bounded scalar source, while fixed-width targets currently
  accept integer and string sources rather than producing unsupported C++;
- authored S2S fixtures retaining both cases for later explicitly requested native
  validation.

### Native validation closeout

The explicitly requested native checkpoint at commit `0746c2e4` converted and built
the compiler with Clang 18 and `--no-stan`, compared PHP-host and native-compiler
C++ byte-for-byte, and compiled and executed all 104 valid authored S2S fixtures.
The run included all cast fixtures, retained the 15 floating-point spelling checks,
matched 32 rejection/recovery cases and passed an incremental rebuild. LLVM and
legacy STAN remained parked. The reproducible evidence path and command are recorded
in the [portability review](../portability/conversion_review.md).
