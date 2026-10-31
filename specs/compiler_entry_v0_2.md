# v0.2 compiler integer entry slice

Doc Status: normative
Scope: the bounded PHP compiler rebuild under `v0.2/compiler/`.

## Source contract

The first executable slice admits a single file containing explicitly typed integer
local declarations followed by a top-level value return. Its equivalent forms are:

```text
PHP: $ret int = 8; return $ret;
C++: int ret = 8; return ret;
JS:  let ret: int = 8; return ret;
```

`int` currently selects the canonical signed int64 type; `int64` explicitly names
the same type. This fixes the default for this bounded implementation, not a PRJ
profile schema. Integer expressions are nonnegative decimal literals or local name
reads. Leading zeros do not change the numeric base. Literal magnitude is checked
before converting to a host integer; 9223372036854775807 is the largest admitted
literal. Parsing preserves spelling and does not perform that range check.

Locals are visible only after successful initialization, in source order. Duplicate
declarations in this single scope, self-initialization, unresolved names, reads
before declaration and unknown/unsupported type names receive source-located
preparation diagnostics. A reference binds to its collected declaration identity.
Known integer values propagate through local initialization and name reads so
statically failing exit conversions are diagnosed before emission.

The executable slice requires an explicit value return and supports no following
statements. Inferred declarations and assignments may still be parsed, but their
preparation is outside this slice. The function extension below admits bare return
inside explicit void functions, but not in the executable entry. Arithmetic, unary
signs, nested blocks, runtime input expressions and incremental compilation remain
outside this implementation. These limits do not prohibit those features in the
broader language.

## Entry exit boundary

A top-level return supplies the executable's process exit value. The example must
compile and run with exit status 8 in all three flavors.

The source integer converts to the target C++ signed `int`, using the target's
numeric limits, not the PHP host's `int` width. If its value is known during
preparation and outside those limits, preparation rejects it. Otherwise preparation
records either a proven conversion or a required runtime check. The runtime check
evaluates the value once, rejects an out-of-range value with `std::out_of_range`,
and only then narrows it. No silent truncation or wrapping is permitted by this
conversion. The operating system may subsequently limit the reported exit status.

The current source subset produces known values. The unknown-value decision and
its native adapter are tested independently; this is not a claim that the source
compiler already supports runtime input or exceptions.

## Stage and publication boundaries

Collection records syntax occurrences and file-local declaration identities without
resolving names, types or visibility. Preparation performs a source-order pass,
retains typed facts, and selects the entry conversion. C++ generation accepts only
completed prepared entry/program facts and does no name resolution or source validation.
Type spellings, safe C++ variable names and native conversion syntax are backend-owned.

The target adapter reads the existing layout probe's signed native-int facts.
Generated code asserts the corresponding native-int signedness and value-bit count,
rejecting compilation against a different integer target. This does not implement
cross-compilation orchestration or a complete runtime ABI packaging protocol.

Emission renders a whole artifact before touching its output path. File publication
stages in the destination directory and replaces the destination by rename. A failed
parse or preparation creates no new output and leaves an existing artifact intact;
the caller must honor failure and must not build or run that older artifact as a
successful result. The compiler never overwrites its source or target-facts input.

The [preparation diagnostic policy](v0_2_preparation_diagnostics.md) remains the
general authority for early rejection versus value-dependent runtime guards.

## Bounded multifile executable

A program explicitly lists its sources with a flavor per file and selects exactly
one entry by canonical root-relative path. The entry must belong to that inventory.
Every source is parsed and collected once per invocation. Only the selected entry
may contain executable statements; secondary files admit whitespace, comments and
the function definitions described below. Calls can resolve functions across the
explicit source inventory.
No execution order or dormant executable body is invented for secondary files.

The backend emits one C++ translation unit per source plus one entry adapter owning
`main`. Source-unit names derive from logical paths, independently of inventory
order and source-root location. The manifest records the source-to-unit mapping and
exact translation-unit list. Native builds consume that list, not a directory glob.

All sources must pass validation before publication starts. A failed source leaves
previous output intact and creates no new output directory. Successful publication
retires only obsolete units listed in the previous manifest, preserving unrelated
files. Individual files are replaced atomically and the manifest is published last;
this is not an atomic bundle transaction. Interrupted publication recovery and
concurrent writers are outside this slice. Callers must honor command failure.

## No-argument void function extension

The bounded compiler supports namespace-free function definitions with no parameters
and an explicit `void` return type. Equivalent forms are:

```text
PHP: function ping(): void { return; }
C++: void ping() { return; }
JS:  function ping(): void { return; }
```

All flavors call these functions with `ping();` in statement position. A function
body may be empty or fall through, or end with a bare `return;`. Returning any value
from a void function is invalid. Bodies may contain the existing explicit integer
local declarations and no-argument calls; each body has its own local scope and
cannot capture top-level or other functions' locals. Local source-order visibility
rules remain unchanged. Executable statements after a return remain unsupported.

Function definitions are global declarations, not executable statements. They may
appear before or after top-level executable statements, including after the entry's
value return. Definitions may appear in secondary sources. Only the selected entry
owns top-level executable statements and still requires its integer value return.
Nested function definitions are not supported.

Names are case-sensitive and resolve in one global function-name domain over the
explicit source inventory. Function calls use this domain, independently of local
integer bindings; variable-call syntax and function values are not supported.
Forward calls and self/mutual recursion are valid because every callable signature
settles before any executable body is prepared. Duplicate function names are invalid;
this extension does not introduce overloads. Unknown functions receive source-located
preparation diagnostics. Invalid bodies are rejected even if never called.

Parameters, call arguments, non-void/inferred return types, prototypes without bodies,
methods, namespaces, function values, callable expressions in value position and
incremental reuse are outside this extension. Unsupported forms diagnose in the
frontend or preparation; generated C++ is not their semantic validator.

The coordinator builds a pending symbol index from parser discovery notifications.
It publishes only after every source parses successfully. Declaration identity,
settled signature and prepared body are distinct: signatures are attached to their
declaration nodes, while body facts are attached to their body owners. Call facts
retain the resolved contract and do not inspect the target's executable body.
Completed prepared-program/entry results gate emission after all bodies succeed.

Each source emits its own function definitions with deterministic backend-owned C++
names and the prototypes needed for its calls. No source embeds a foreign function
body. Cross-file calls link through the existing exact translation-unit manifest.
Stable function names and source-local output boundaries prepare for future
incremental work but do not establish reuse or invalidation today.

## Bounded echo extension

Entry and void function bodies accept `echo value;` in all three flavors.
This implementation subset follows [the output contract](output_v0_2.md): output
has no implicit separator or newline, integer formatting is decimal, and string
output preserves bytes. Each operand is evaluated once.

The current implementation admits one existing int64 literal/local or one quoted
string literal. Single quotes decode only escaped quote/backslash. Double quotes
support `\\`, `\"`, `\$`, `\n`, `\r`, `\t` and isolated `\0`. Unsupported escapes
and interpolation diagnose. General string expressions and multiple operands are
not yet implemented; these restrictions do not redefine the full output contract.
See [implementation and debt](../v0.2/compiler/docs/output_slice.md).

## Implicit entry representation

Top-level executable statements are represented internally as the body of a
file-private implicit `main` declaration, first in the file AST's keyed declaration
collection. It is excluded from global function lookup. `main` is reserved for
this entry role; explicit source declarations of it remain unsupported. This does
not change the existing selected-entry or exit-status rules and does not admit
new function forms.
