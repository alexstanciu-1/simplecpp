See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.

# Prism++ Runtime Generation Guidelines

## Purpose
This document defines how the runtime code must be generated so that repeated generations remain structurally consistent, reviewable, and aligned with the project spec and config.

## Inputs
Generation must use these inputs as the canonical sources:

- `spec.md`
- `config.json`
- `operator_generation_flow.md` for the generated canonical operator-surface procedure

Role split:

- `scpp_runtime_spec.md` defines human rules, semantic intent, invariants, and generator constraints.
- `scpp_runtime_config.json` defines machine-owned data used for generation, including types, values, casts, overloads, and assignment rules.

If the two conflict, the conflict must be reported explicitly instead of guessed around.

### Runtime/frontend boundary
When generator behavior must temporarily diverge from the long-term runtime ideal for language-usability reasons, the divergence must be documented in `../../specs/dynamic_types.md` (Explicit Typed Boundaries / Technical Compromises) rather than hidden in runtime prose. For v1, those sections are normative priority rules: runtime cleanup must not remove required typed-destination bridges until generator parity exists.

Generation must preserve a strict layering boundary:

- the runtime is a semantic C++ library surface, not a validator for source-language legality
- unsupported or invalid PHP-in-subset constructs must be rejected by analysis/lowering before runtime semantics are involved
- generated code may rely only on documented runtime contracts, not on hidden runtime knowledge of frontend phases
- runtime defensive checks may protect internal invariants, but they must not encode frontend policy

## Output layout
Generation must target the project runtime root and produce files by category, not by ad-hoc manual decisions.

Expected layout logic:

- `include/scpp/`
  - one header per generated runtime type or focused component
  - one aggregation header: `runtime.hpp`
- `tests/`
  - generated or maintained tests grouped by purpose
- root build files
  - generated or updated only if generation rules explicitly cover them

### Header production rules
Use these rules for file creation:

- create one dedicated header for each primary runtime type:
  - scalar wrappers
  - sentinel wrappers
  - pointer wrappers
  - container wrappers
- create shared support headers only when multiple generated headers depend on the same reusable logic
- keep `runtime.hpp` as the stable umbrella include that pulls in the generated public headers
- treat `runtime.hpp` as the mandatory public entry point for generated operator availability
- do not require individual wrapper headers to remain operator-complete after operator-surface migration
- do not collapse unrelated types into one large header unless the config explicitly models them as one unit

### File production logic
Generation should work from categories:

1. generate sentinel/value declarations
2. generate scalar wrappers
3. generate template wrappers
4. generate helper functions (memory helpers, cast helpers)
5. generate the canonical public operator surface from config-owned operator rules
6. generate umbrella include
7. generate or update tests from config-owned semantics

This order should be kept stable unless the generator itself is intentionally revised.


## Generated operator-surface rule
The runtime now has an explicit operator-generation procedure captured in `operator_generation_flow.md`.

Generation for migrated operator families must follow these rules:

- generate the public operator entry points centrally under `include/scpp/generated/`
- use stable handwritten concepts only as operand-category filters
- dispatch native-wrapper combinations to internal `detail::...` helpers
- dispatch `mixed_t`-participating combinations to dedicated mixed runtime helpers
- do not keep overlapping public operator definitions in multiple runtime locations once the generated family is fully migrated

This means operator generation is no longer an ad-hoc byproduct of wrapper generation. It is a first-class generated surface with its own migration and cleanup discipline.

## Source-of-truth rules
To keep generations consistent:

- do not hand-invent overloads, casts, assignments, or comparisons outside the config
- do not duplicate config-owned machine detail into the spec
- do not infer missing rules silently
- do not add convenience APIs unless they are either:
  - explicitly required by spec, or
  - explicitly modeled in config

Any extra generated API surface must be treated as drift.

## Commenting requirements
Every generated public or internal code element must be commented.

This includes:

- classes
- structs
- aliases
- constants
- methods
- functions
- operators
- properties / data members
- helper utilities
- template declarations and specializations when generated as public-facing code

Each comment must explain both:

1. what requirement or rule the element covers
2. what the element does operationally

### Comment style rules
Comments should be direct and stable. They should avoid noise and explain intent in generator terms.

Preferred pattern:

- requirement covered
- behavior implemented
- important restriction, if any

Example shape:

```cpp
// Covers the config rule that nullable<T> can be assigned from null_t.
// Clears the optional payload and leaves the wrapper in the empty state.
nullable<T> &operator=(const null_t &value);
```

For data members:

```cpp
// Stores the wrapped native value used to enforce scpp-level type isolation.
std::int64_t value_;
```

## Stability requirements for consecutive generations
Consecutive generations must remain stable when the inputs are unchanged.

Required stability properties:

- same file layout
- same symbol names
- same comment structure
- same ordering of declarations where possible
- same include ordering policy
- same formatting policy
- same generation rules for deleted/allowed operations

A generation run must not produce arbitrary reordering.

### Deterministic ordering
Use deterministic ordering for:

- type emission
- value emission
- cast emission
- overload emission
- assignment emission
- test generation

Recommended ordering source:

- explicit order from config, if present
- otherwise lexicographic order with stable category grouping

## Formatting and style
Generated code must follow the project style consistently.

Required defaults:

- target standard: `C++23`
- namespace root: `scpp`
- comments present on all generated elements
- formatting stable across generations
- no silent style drift between runs

If project formatting rules are later formalized, the generator must treat them as constraints, not suggestions.

## Drift prevention
Generation should actively prevent drift between spec, config, and runtime.

Recommended checks:

- verify every configured type has a generated representation
- verify every configured value constant is emitted
- verify every configured cast rule is either generated or explicitly rejected with reason
- verify every configured overload rule is either generated or explicitly rejected with reason
- verify every configured assignment rule is either generated or explicitly rejected with reason
- flag extra public runtime API not owned by spec or config
- flag build files targeting a different C++ standard than the project target

## Error handling during generation
When generation encounters incomplete or conflicting input:

- fail explicitly
- report the exact conflicting field or rule
- do not generate guessed fallback semantics

Allowed exceptions should be explicit and documented in the spec.

## Test generation guidance
Tests should be derived from config-owned semantics.

Recommended groups:

- positive compile-and-run tests for allowed behavior
- compile-fail tests for forbidden behavior
- drift checks for required public API presence
- targeted tests for sentinels, casts, overloads, assignments, and ownership wrappers

Tests should validate current generated behavior, not hypothetical future behavior.

## Suggested additional rules
These are recommended to keep the generator maintainable:

### 1. Add generator metadata
Each generated file should contain a short header comment stating:

- that it is generated
- which inputs it was generated from
- which generator version produced it

### 2. Define protected manual zones only if truly needed
Avoid mixed manual/generated files. If manual edits are unavoidable, isolate them with explicit protected regions. Otherwise regenerate whole files.

### 3. Keep one responsibility per header
Avoid headers that mix unrelated concepts. This reduces regeneration noise and makes drift easier to detect.

### 4. Generate deleted operations explicitly
For forbidden operations, prefer explicit deletion or omission according to the runtime design, but do it consistently and from config-owned rules.

### 5. Preserve backward-stable public names
Changing public names should require an intentional spec/config change, not emerge from generator refactors.

### 6. Emit validation reports
Each generation should ideally produce a short validation summary with:

- files produced
- rules applied
- skipped rules
- drift warnings
- hard errors

## Recommended minimal generation contract
A generation is acceptable only if all of the following are true:

- spec and config were both read
- no unresolved conflicts were ignored
- output layout followed project rules
- all generated elements were commented
- public API matches config-owned rules
- runtime target remains `C++23`
- consecutive identical inputs would produce materially identical output

## hash_t-specific generation/update rules
Generation and manual updates for `hash_t` must preserve these constraints:

- public generated/runtime-facing code must target `scpp::hash_t`, never donor `mem_container` names
- the donor storage structure may be adapted, but public `hash_t` semantics own the contract
- read-only array access should lower through the direct `operator[]` path on `hash_t` / `mixed_t`; plain reads must stay non-materializing even though typed-reference contexts may bind through the same expression
- normal keyed access should lower to `operator[]`; presence-sensitive paths still use `find()` / `has()`, and checked-failure paths may use `at()`
- text output of a slot-aware dim read must keep slot access non-materializing; route echo/stringification through the slot's value read, not through mutable-ref materialization
- `set()` must overwrite existing keys
- `append()` must follow `max_existing_int_key + 1`
- `123` and `"123"` remain distinct keys at runtime
- code-bearing runtime files may live under `include/scpp/support/`, with forwarding public includes kept in `include/scpp/` when useful


## hash_t regeneration/update safeguards

When generating, updating, or refactoring `hash_t`, preserve these invariants:

- `find()` must remain non-inserting
- `at()` must remain checked and non-inserting
- raw mutating `operator[]` semantics are no longer the generator-facing read contract for `hash_t<mixed_t>`
- `set()` must overwrite existing keys
- `append()` must use `max_existing_int_key + 1`
- integer keys and string keys must remain distinct
- `null_t` is a valid stored value
- `nullopt_t` represents lookup miss, not stored null

### Removal safety rule

Removal must not shift visible keys.

In particular:

- do not use direct packed-vector erase if it changes later numeric keys
- do not renumber keys after removal
- if packed storage cannot preserve key stability during removal, promote to associative mode first or use tombstones

### Required regression case

This case must remain true after every update:

```cpp
hash_t t;
t.append(100); // key 0
t.append(200); // key 1
t.remove(0);

assert(!t.has(0));
assert(t.has(1));
assert(t.append(300) == 2);
```

