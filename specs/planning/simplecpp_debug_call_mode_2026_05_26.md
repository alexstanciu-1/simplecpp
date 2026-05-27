# SimpleC++ Debug Call Mode
Doc Status: planning

Date: 2026-05-26

Purpose:

- define a practical first design for `scpp debug --call`
- keep `--call` separate from source-rewrite debug actions
- define the JSON input conversion boundary clearly before implementation
- define the first concrete support matrix, validation boundary, harness shape, and failure model

This note is planning guidance, not semantic authority.

## Main Rule

`--call` is not a source-rewrite feature.

Current required rule:

- `--call` must not be implemented by rewriting project source files
- `--call` should use a dedicated debug harness path
- JSON input conversion should only be allowed for parameter types with an explicit from-json conversion contract

This keeps `--call` distinct from simple source-site actions such as:

- `--exit=file:line`
- `--break=file:line`
- `--dump-before=file:line:expr`
- `--dump-after=file:line:expr`

## Why This Boundary Exists

`--call` is different from source-targeted debug controls.

It does not describe:

- a source file
- a line
- a before/after injection site

Instead, it describes:

- a callable target
- a list of input values
- a conversion step from JSON into Prism++ runtime values

So it should be treated as a harness-invocation mode, not as a rewrite-the-source mode.

## Proposed User Shape

Examples:

```bash
scpp debug --call 'sum_values' --call-args '[1,2,3]'
```

```bash
scpp debug --call 'User::loadById' --call-args '[42]'
```

Possible later additions:

- `--call-args-file=<path>`
- `--named-args=<json>`

First-slice posture:

- `--call` should support top-level functions first, then static methods and instance methods through the same harness path
- `--call-args` should be a JSON array in parameter order
- `--call-this=<json>` should be used for instance methods
- no named-argument calling in the first slice
- instance receiver construction should use the same explicit `from_json<T>` contract as ordinary parameters

## First Implementation Model

Preferred pipeline:

1. CLI parses `--call` and JSON input options
2. STAN resolves the callable target and its parameter list
3. the system validates whether each parameter type supports from-json conversion
4. a dedicated debug harness is generated for that callable
5. the harness parses input JSON and converts values
6. the harness invokes the callable
7. result/error events are emitted in the standard debug event stream

## Supported Type Matrix

The first implementation should be explicit and narrow.

### Supported in the first slice

| Prism++ target type | JSON input shape | Notes |
| --- | --- | --- |
| `bool_t` | JSON boolean | only `true` / `false` |
| `int_t` | JSON integer number | reject fractional numbers |
| `float_t` | JSON number | integer JSON values may also convert |
| `string_t` | JSON string | no implicit number-to-string coercion |
| `mixed_t` | any JSON value | converts through a generic JSON-to-mixed path |
| `dynamic_t` | any JSON value | converts through a generic JSON-to-dynamic path |

### Callable target shapes supported in the current slice

| Target shape | CLI form | Notes |
| --- | --- | --- |
| top-level function | `--call='sum_values'` | resolved through STAN symbol metadata |
| static method | `--call='ClassName::method'` | no `--call-this` needed |
| instance method | `--call='ClassName::method' --call-this='<json>'` | `this` must be constructible through `from_json<T>` |

### Supported recursively in the first slice

| Prism++ target type | JSON input shape | Condition |
| --- | --- | --- |
| `nullable<T>` | JSON `null` or valid `T` value | only when `T` is already supported |
| `vector<T>` | JSON array | only when `T` is already supported |
| `hash<T>` | JSON object | only when `T` is already supported |

Recursive support rule:

- `T` may itself be:
  - `bool_t`
  - `int_t`
  - `float_t`
  - `string_t`
  - `mixed_t`
  - `dynamic_t`
  - `nullable<U>`
  - `vector<U>`
  - `hash<U>`
- recursive support is allowed only when the full nested type graph bottoms out in supported leaf types
- the first slice should still enforce practical payload/recursion limits during conversion, even if those limits are not yet exposed as user-facing knobs

### Not supported in the first slice

| Prism++ target type family | Reason |
| --- | --- |
| arbitrary user-defined classes without `from_json<T>` support | no constructor/hydration contract yet |
| wrappers with sentinel semantics outside `nullable<T>` | need explicit mapping rules first |
| ad hoc implicit coercions | too ambiguous for a debug harness contract |

## From-JSON Conversion Contract

Current preferred rule:

- a parameter type is callable from debug JSON only if there is an explicit supported conversion from JSON into that Prism++ type
- no best-effort or fuzzy coercion should be treated as part of the contract
- unsupported types should fail clearly

Preferred runtime API surface:

- the conversion contract should be expressed as a generic runtime entry:
  - `from_json<T>(json_value)`
- the harness generator should target that generic contract surface rather than inventing one-off per-call conversion logic
- explicit helper/specialization machinery may still exist underneath, but the design contract should stay generic at the call site
- the first owning header should be:
  - `runtime/include/scpp/json/from_json.hpp`
- this conversion contract should live in a runtime-owned, non-debug-specific JSON subsystem

Why this shape is preferred:

- one mental model for all supported call-debug input conversion
- natural recursive support for `nullable<T>`, `vector<T>`, and `hash<T>`
- cleaner harness generation because the generator only needs the resolved target type `T`
- easier to keep conversion failures consistent and structured

Required behavior of `from_json<T>(json_value)`:

- succeed only for explicitly supported `T`
- reject unsupported `T` clearly
- apply strict target-type rules, not PHP-style coercions
- support recursive conversion only when nested `T` is itself supported
- produce path-aware conversion diagnostics when practical

## Runtime Placement

Preferred owning location:

- `runtime/include/scpp/json/from_json.hpp`

Possible nearby supporting files as the implementation grows:

- `runtime/include/scpp/json/json_value.hpp`
- `runtime/include/scpp/json/from_json_error.hpp`
- `runtime/include/scpp/json/from_json_path.hpp`

Current design rule:

- `--call` consumes this runtime conversion surface
- the runtime conversion surface is not owned by the debug subsystem
- future non-debug runtime features may reuse the same JSON conversion contract

## First Runtime Surface

The first slice should keep the runtime surface small.

Preferred conceptual API:

```text
template<typename T>
T from_json(json_value value);
```

Companion helper shape:

```text
struct from_json_error {
    string_t message;
    string_t path;
    string_t target_type;
    string_t category;
};
```

The exact runtime error carrier may differ from this sketch, but the first implementation should preserve at least:

- a human-usable message
- the JSON path where conversion failed when practical
- the intended target type

## First Conversion Rules

The first implementation should define explicit runtime rules for:

- `bool_t`
- `int_t`
- `float_t`
- `string_t`
- `mixed_t`
- `dynamic_t`
- `nullable<T>`
- `vector<T>`
- `hash<T>`

Strict rules:

- `bool_t` accepts only JSON booleans
- `int_t` accepts only integer-compatible JSON numbers
- `float_t` accepts JSON numbers
- `string_t` accepts only JSON strings
- `nullable<T>` accepts JSON `null` or a valid `T`
- `vector<T>` accepts only JSON arrays
- `hash<T>` accepts only JSON objects

Recursive rules:

- nested conversion is allowed only when the nested `T` is already supported
- container/object conversion should propagate path information for element/key failures
- internal recursion/size guards should exist even before they are user-configurable

## Validation Boundary

Current preferred rule:

- if STAN can resolve the callable signature and determine that a parameter type has no supported from-json conversion path, the debug request should fail before build
- if the type is supported in principle but the provided JSON payload is invalid for that type, the failure should happen at runtime in the harness
- if a conversion helper is expected but missing lower in the implementation, that is an implementation/runtime contract failure
- this same split applies to `--call-this` for instance methods

This gives three distinct failure families:

1. validation-time unsupported type
2. runtime invalid input payload
3. implementation contract failure

Concrete current example:

- `--call='DemoGreeter::suffix' --call-this='{"name":"unused"}' --call-args='["Ana"]'`
  may still fail at build time today when `from_json<shared_p<DemoGreeter>>` is not defined
- that is expected until a user-defined receiver hydration contract exists

### Validation-Time Checks

These should happen before harness generation whenever possible:

1. resolve the target callable
2. confirm the callable shape is allowed in the current slice
   - top-level function only in the first slice
3. confirm arity matches the declared parameter count
4. confirm each parameter type is visible and supported by the from-json matrix
5. reject unsupported parameter families early

Examples of validation-time failure:

- callable cannot be resolved
- callable is an instance method in the first slice
- JSON argument count does not match the parameter count
- parameter `UserRecord` has no supported from-json path
- parameter type is not visible enough to classify safely

### Runtime-Time Checks

These should happen inside the generated harness:

1. parse the JSON payload
2. check the top-level payload shape
   - first slice requires a JSON array for `--call-args`
3. convert each positional input into the declared Prism++ target type
4. call the target
5. emit result or runtime failure events

Examples of runtime failure:

- invalid JSON text
- JSON array element cannot convert to `int_t`
- JSON object provided for a `string_t` parameter
- callable itself throws or fails during execution

## JSON Mapping Posture

Current preferred posture:

- deterministic and explicit
- narrow in the first slice
- no PHP-style loose coercion assumptions

Examples:

- JSON `true` -> `bool_t`
- JSON number -> `int_t` or `float_t` only when compatible with the target rule
- JSON string -> `string_t`
- arbitrary JSON value -> `mixed_t` / `dynamic_t`
- JSON `null` or valid nested value -> `nullable<T>`
- JSON array -> `vector<T>`
- JSON object -> `hash<T>`

Recommended strictness:

- no implicit stringification
- no implicit truthiness coercion
- no implicit object-to-array or array-to-object conversions
- no acceptance of fractional JSON numbers for `int_t`
- no implicit key/value reshaping for `hash<T>`

## Harness Shape

`--call` should use a dedicated callable harness, not source rewrite.

### First-Slice Harness Responsibilities

The generated harness should:

1. load a serialized debug-call request
2. parse `call_args_json`
3. convert positional JSON arguments into Prism++ values
4. invoke the resolved callable
5. emit structured debug events for:
   - `session_start`
   - optional `call_start`
   - optional `call_result`
   - `runtime_error` on failure
   - `session_summary`

### Suggested Plan Shape Additions

For `mode = function`, the normalized plan should include:

```json
{
  "mode": "function",
  "target": {
    "project_root": "/abs/project",
    "entry": {
      "kind": "function",
      "callable": "sum_values",
      "resolved_file": "/abs/project/src/math.phs",
      "resolved_line": 12
    }
  },
  "inputs": {
    "call_args_json": "[1,2,3]"
  }
}
```

The callable harness itself should be generated into the shared debug workspace, alongside the other debug-session artifacts, rather than by rewriting project authoring sources.

### Conceptual Harness Flow

```text
debug command
  -> resolve callable
  -> validate signature support
  -> emit temporary call harness
  -> build harness
  -> run harness
  -> emit structured events
```

### Conceptual Pseudocode

```text
args_json = load_call_args_json()
decoded = parse_json(args_json)
arg0 = from_json<bool_t>(decoded[0])
arg1 = from_json<int_t>(decoded[1])
result = target_callable(arg0, arg1)
emit_call_result(result)
```

The actual lowering should use project/runtime-native helpers, but this is the intended shape.

The concrete generated harness should include the owning runtime header:

```text
#include "scpp/json/from_json.hpp"
```

and should emit typed conversions conceptually like:

```text
arg0 = from_json<bool_t>(decoded[0])
arg1 = from_json<vector_t<string_t>>(decoded[1])
```

## Failure Model

`--call` should expose failures in the same structured-debug style as the rest of the feature.

### Failure Families

1. `validation_error`
2. `build_error`
3. `runtime_error`
4. `implementation_contract_error`

### Validation Error

Use when the request is not admissible before build.

Examples:

- unknown callable
- unsupported callable shape
- unsupported parameter type
- wrong argument count

Preferred event/body fields:

- `message`
- `category = "debug_call_validation"`
- `subcategory`
- `callable`
- `parameter_index` when relevant
- `parameter_type` when relevant

### Build Error

Use when the generated call harness cannot be built.

Examples:

- missing generated helper
- unresolved runtime support symbol
- emitted C++ build failure in the harness

Preferred event/body fields:

- existing `build_error` fields
- plus `callable` when available

### Runtime Error

Use when the call harness runs but cannot parse/convert/invoke successfully.

Examples:

- invalid JSON text
- wrong JSON shape for the first-slice positional array contract
- conversion failure for one argument
- callable body throws/fails during execution

Preferred event/body fields:

- existing `runtime_error` fields
- `category = "debug_call_runtime"` for harness-owned input failures
- `subcategory` such as:
  - `invalid_json`
  - `wrong_payload_shape`
  - `arg_conversion_failed`
  - `callable_runtime_failure`
- `argument_index` when relevant
- `target_type` when relevant

### Implementation Contract Error

Use when the design says a conversion/helper should exist, but the implementation is incomplete or inconsistent.

Examples:

- type matrix says `int_t` is supported but no conversion helper exists
- harness expects a shared helper symbol that is absent

This is mainly an engineering diagnostic category rather than a user-facing feature case, but it should still fail clearly rather than collapsing into a vague runtime message.

## Harness Ownership

Preferred ownership:

- CLI/debug layer owns `--call` UX and plan shape
- STAN owns callable resolution and signature visibility
- generator owns callable harness emission
- runtime/shared helpers own from-json conversion helpers and structured result/error helpers

## Relationship To Source Rewrite

Required boundary:

- `--call` must not trigger project-wide source rewrite
- `--call` must not depend on file-by-file helper insertion
- if a later `--call` session also includes explicit `file:line` actions, those source-site actions may still use source rewrite independently

So the system may combine:

- callable harness execution
- explicit source-site actions

but the callable invocation mode itself should not be implemented by source rewrite.

## Recommended Next Step

Before implementing `--call`, define:

1. the first supported from-json type matrix
2. the validation-time unsupported-type failure shape
3. the runtime invalid-input failure shape
4. the callable harness shape for top-level functions

That concrete shape is now defined in this note. The next implementation note should focus on:

1. exact `DebugPlan` fields for `mode = function`
2. the first runtime helper API for from-json conversion
3. the first generated harness shape for top-level functions only
