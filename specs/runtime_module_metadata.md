# Runtime module metadata — version 1
Doc Status: normative
Status: bounded v0.2 catalog format

Runtime-owned catalogs live in `runtime/metadata/modules/<module>.json`, outside any
compiler implementation. They describe callable/constant semantics independently of C++
bindings. The consumer describes concrete and inferred generic free-function contracts; it is not
a declaration of complete runtime coverage or a replacement of v0.1 metadata yet.

## Shape

Every module object requires these fields:

- `version`: integer `1`.
- `module`: nonempty lowercase ASCII letters/digits/underscore name, matching its filename.
- `dependencies`: ordered array of module names, without duplicates or cycles.
- `functions`: ordered array of function contracts.
- `cpp`: array of separate C++ bindings, joined to functions by `id`.

An optional `constants` array contains module-owned scalar constants. Omission means
no constants. The initial supported form is:

```json
{"name":"STR_PAD_LEFT","type":"int","value":0}
```

Each entry requires exactly `name`, `type`, and `value`. `name` is the nonempty,
case-sensitive source spelling; `type` currently must be `int`. `value` is a JSON
integer spelling in the signed-64 range, not a string, fractional number or exponent.
The JSON reader checks the range without floating-point conversion; the settled
value retains normalized decimal text. Other scalar constant types are deferred.
Duplicate names across the selected modules or collisions with language constants
reject the entire configuration. Nearer source declarations may still shadow them
under ordinary lexical lookup. Constants use the existing settled constant model
and value lowering, not function contracts or C++ symbol bindings. Their values are
part of the trusted runtime contract and must agree with the provider implementation.

A function requires `id`, `name`, `parameters`, and `returns`. Identities are globally
unique across selected modules. Source names may repeat for overloads with different
parameter types; return type or passing mode alone does not distinguish overloads.
Each parameter requires a unique `name`, a `type`, and `passing` (`value`, `reference`,
`readonly_reference`, or `observe`). These names are metadata, not named-argument support.
Reference-bearing signatures must have an unoverloaded source name in this slice;
ordinary call admission still checks reference places and access.

### Non-retaining observation

`observe` describes a runtime-only, read-only input boundary, not an ordinary source
reference parameter. The provider promises not to mutate the input, retain a borrow,
or return a value that borrows from it. Admission requires the exact storage type,
but does not require copyability or a stable reference place. Locals, readonly
inputs, temporary values and property expressions may be observed without copying
or consuming their storage. Evaluating the supplied expression may itself have
effects; it runs exactly once.

The current bounded consumer accepts one fixed required observation input and a
value return. Defaults, variadics, reference returns, static factories and source
observation declarations are not enabled. An overload set cannot mix observation
and consuming/reference input contracts. The trusted provider is responsible for
the non-retention promise; metadata cannot prove arbitrary C++ implementation bodies.

C++ lowering passes a `const T&` view in the call's full expression. Argument and
receiver temporaries therefore survive the call; a property of a temporary owner
must not be staged into a reference whose owner has already been destroyed.
Multi-argument observation is deferred until argument-induced invalidation rules
are established. This does not relax ordinary source reference lifetime rules.

A type is either a source-exposed registered name such as `"string"`, or an application:

```json
{"name": "result", "arguments": ["dynamic"]}
```

Arguments recursively use this same representation. Resolution delegates identity,
arity and formation checks to the type registry; JSON contains no canonical type IDs,
source ASTs, C++ type spellings or compiler callbacks. Void is not a parameter type.

A C++ binding requires `id`, `symbol`, and `header`. Each function has exactly one
binding in its declaring module, and no orphan bindings are allowed. Symbols are
qualified names, not C++ expressions; headers are include-path spellings, not source
snippets. These are trusted toolchain catalogs, not executable user extensions.
The backend validates and consumes bindings; semantic call preparation does not.

Unknown fields, wrong JSON kinds, duplicate decoded keys, unsupported versions,
unresolved types, invalid template applications and duplicate contracts are errors.
There is no silently ignored future-schema field.

## Inferred generic runtime contracts

A function may add a nonempty `type_parameters` array of unique names. A type
variable is spelled `{"parameter":"T"}`, distinct from the registered name `"T"`.
It cannot also have `name` or `arguments`. Variables may occur recursively in input
and return types; every declared variable must occur in at least one input. Return
context does not infer variables. Unbound/unused variables and invalid application
arity reject during catalog installation. Omitted template arguments use the type
registry's defaults, not wildcards.

For example, the vector count contract is:

```json
{
  "id": "core.count.vector",
  "name": "count",
  "type_parameters": ["T"],
  "parameters": [{
    "name": "value",
    "type": {"name": "vector", "arguments": [{"parameter": "T"}]},
    "passing": "observe"
  }],
  "returns": "int"
}
```

Generic inputs are fixed required value parameters or the bounded unary observation
contract. Generic reference inputs, defaults, variadics, user-authored generics,
explicit source type arguments and explicit variable capability constraints remain
outside this slice. Variables impose no implicit copyability constraint: the formed
type and selected value boundary own storage/copy requirements.

Installation resolves named parts to registry identities once, retaining a type-pattern
recipe rather than a placeholder concrete signature. Preparation evaluates argument
types once, matches exact application identities and ordered arguments, and requires
repeated occurrences of a variable to agree exactly, including storage modifiers.
It does not rank conversions or prefer concrete/more-specific patterns: zero matches
or multiple matches are preparation errors. Equivalent positional recipes with renamed
variables are duplicate contracts. Other overlapping recipes are rejected when a call
matches more than one. Untyped data literals need an explicit target where inference
cannot determine their type independently of the candidate.

Only the selected recipe produces a concrete call-owned signature. Application
formation uses the ordinary registry and preparation capability/dependency queries.
No source-derived capabilities or per-call signatures are cached in the provider.
The C++ backend reads the selected signature and the original provider binding;
C++ template argument deduction handles its runtime implementation. Parsing and
collection contain no runtime-function-name-specific paths.

## Loading, ownership and publication

The composition root supplies a catalog directory and selected modules. Load each
selected/dependent module once, dependency-first, directly through the shared JSON
pull reader into typed metadata. No mixed/dynamic or generic document tree is built.
Complete document/schema validation precedes publication; failed loading/configuration
does not replace the previous configuration.

Metadata is retained in memory as immutable-by-contract startup input. Resolve
signatures and constants into separate private inventories against the current canonical type
registry; only validated identities enter language scope. C++ bindings are installed
separately by the backend under coordinator sequencing. Ordinary compilation and
incremental updates perform no metadata IO or JSON parsing.

Semantic registry resets reuse retained typed metadata but recreate canonical type,
callable, constant and backend-binding identities. Old targets cannot reuse new bindings merely
because their string IDs match. Catalog edits take effect in a new compiler session;
live catalog replacement/hot reload is outside this slice.

The current my-try host selects `core`, `json`, `strings`, `datetime` and `filesystem`, preserving the
JSON/string APIs and enabling the established strict datetime/filesystem surfaces. Native validation snapshots
these same files and supplies their location
explicitly; installed/distributed compiler packaging must likewise supply resources.
Runtime-link/build-module policy remains with existing build owners. Catalog dependencies
order metadata loading; they do not yet configure linker inputs automatically.

## Initial contents and next boundary

`regex` is available as a separately selected catalog; it is **not** added to the
default module selection. It depends on core and preserves the existing opt-in
PCRE2-8 requirement. Catalog selection does not install PCRE2 or link its provider:
build consumers must supply `modules/regex/regex.cpp` and PCRE2-8. Its bounded
contents are `regex_jit_available`, both `regex_quote` arities, two/three-argument
forms of `regex_match`, `regex_match_named`, `regex_match_all`,
`regex_match_all_pattern_order` and `regex_match_all_named`; two-argument
`regex_grep`; three-argument `regex_replace`; and two/three/four-argument `regex_split`.
It exposes `PREG_SPLIT_NO_EMPTY=1` and `PREG_SPLIT_DELIM_CAPTURE=2` as int constants.
These are existing contracts from [regex intake](builtins/regex/first_pass.md), not
new aliases or implicit container-to-bool conversions. Empty successful captures
remain successful Result values. Filter, callbacks, count outputs and limited
replacement remain outside this bounded catalog pending the
[documented prerequisites](../compiler/my-try/docs/planning/runtime_catalog_coverage.md#regex-module-inventory--2026-10-09).

- `core`: `strlen(string): int`, counting bytes through `scpp::str::byte_length`;
  `count(vector<T>)`, `count(hash<T,K>)`, `count(mixed)` and `count(dynamic)`, all
  observing inputs and returning `int` through `scpp::count`. Boxed kinds are checked
  at runtime; statically unsupported carriers reject during preparation.
- `json`: existing dynamic/mixed decode contracts and both encode overloads, preserving
  Result wrapping and family separation.
- `datetime`: all eleven strict `dt_*` helpers from the datetime builtin contracts.
  Monotonic microsecond/nanosecond results remain `uint64`; parsing returns `result<int>`;
  sleep returns `void`. Legacy `time`, `date` and `strtotime` aliases are not enabled.
  The build consumer must link the datetime provider object as well as core runtime;
  successful semantic registration does not imply automatic native-module linkage.
- `filesystem`: nineteen strict `fs_*` helpers: path probes, whole-file get/put,
  directory creation/scanning/removal, size/mtime/touch, entry removal/copy/rename,
  canonical/lexical paths and checked `fs_read_snapshot`. Fallible reads return
  `result<string>`, `result<int>` or `result<vector<string>>`; ordinary mutations
  and probes retain their boolean contracts. The snippet `core/runtime.cpp` already
  bundles the filesystem and snapshot providers; do not link duplicate standalone
  objects beside it. File-lock handles require registered
  runtime types and remain unregistered. `fs_scan` uses shared container-payload
  Result operations without a module-specific extraction path. Internal `scpp_output_*` portability
  bindings are not public my-try source APIs; see [output ownership](portability/output_files.md).
- `strings`: `strtolower`, `strtoupper`, `lcfirst`, `ucfirst`, `str_contains`,
  `str_starts_with`, `str_ends_with`, both arities of `trim`/`ltrim`/`rtrim`, `bin2hex`
  and three-string `str_replace`, plus `str_pad` (2/3/4 arguments) and `number_format`
  (int/float, 1/2/4 arguments), bound to the existing `scpp::str` implementations.
  Padding constants are `STR_PAD_LEFT = 0`, `STR_PAD_RIGHT = 1`, `STR_PAD_BOTH = 2`.
  Formatting does not admit bool/string/mixed/dynamic inputs in this slice; boxed
  numeric-kind validation remains deferred. Padding validates empty padding and
  invalid modes even when the requested length would otherwise be a no-op.
  Case changes are ASCII/byte-oriented, not Unicode case folding. Predicates are
  byte-oriented and accept an empty needle. These fixed contracts use ordinary
  value arguments: ordered argument snapshots remain alive through the runtime call;
  this does not enable multi-argument observation.
  Trim masks are byte sets (no range expansion); omitted masks select the runtime's
  one-argument overload. Hex encoding is lowercase and binary-safe. Replacement is
  non-overlapping with an empty-search no-op; array forms and a count output are not
  enabled. Multiple documented arities use separate fixed contracts, not optional
  metadata parameters or new default-argument lowering.
  The module also registers `explode` (2/3 args, `vector<string>`), `implode`
  (`vector<string>` or inferred `hash<string,K>`), code-point `substr` (2/3 args),
  byte-based `substr_compare` (3/4/5 args), and `hex2bin` with its documented
  `result_or_false<string>` contract through the existing PHP adapter.
  Explicit byte helpers (`string_byte_len`, `string_byte_at`, `string_byte_find`,
  `string_byte_slice`, `string_byte_slice_equals`), UTF-8 helpers
  (`string_utf8_codepoint_count`, `string_utf8_codepoint_at`,
  `string_utf8_slice_codepoints`) and conservative grapheme helpers
  (`string_grapheme_count`, `string_grapheme_slice`) retain their existing units.
  `string_byte_find` returns `result_or_false<int>`, preserving position zero as
  success; internal nullable/Result helpers are not substituted for public adapters.
  All these added parameters use ordinary value boundaries, not observation.

The strings module's unresolved search-coordinate and replacement contracts, boxed
joining/formatting, and unregistered builder types are excluded from executable JSON.
See the [module inventory](../compiler/my-try/docs/planning/runtime_catalog_coverage.md#strings-module-inventory--2026-10-09).
The byte-length decision for `strlen` does not change `substr` or explicitly named
Unicode APIs. Explicit UTF-8 helpers reuse runtime behavior; they do not add the
separate portable-compiler framework's UTF-8-validation contract.

New runtime type declarations, fixed-array non-type parameters, compiler-module
Storage registration, optional/default
and variadic arguments, reference-return lifetime contracts, static runtime factories,
profile availability and the rest of the runtime inventory are not encoded by this
version's bounded consumer yet. Extend the shared models before enabling these rows;
do not generate fake concrete overloads or function-name-specific preparation paths.
