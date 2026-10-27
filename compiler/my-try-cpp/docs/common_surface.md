# Common compiler-facing runtime surface
Doc Status: supporting

Common names are exposed directly inside `scpp::compiler`, through explicit
imports in the header that owns the facility. This is ordinary C++ with short
names, not a new scripting syntax. There is no umbrella include, global namespace
import or `using namespace compiler_support` in compiler code.

| Header under `scpp_compiler/` | Unqualified names in `scpp::compiler` |
| --- | --- |
| `types.hpp` | `int32`, `uint32`, `int64`, `uint64`, `boolean`, scalar `isset` |
| `string.hpp` | `string`, `strlen`, string `empty` and `isset` |
| `textbuilder.hpp` | `textbuilder` |
| `filesystem.hpp` | `fs_read`, `fs_canonical_directory` |
| `datetime.hpp` | `monotonic_micros` |
| `io.hpp` | `print_line` |
| `host.hpp` | startup error helpers and the native `run_host` adapter |

`types.hpp` has no includes. The other focused headers include only their API
dependencies. Vector/hash additions and common imports are deferred. Existing
concrete vector facades remain explicitly selected by the compiler code that
already uses them; their scalar size/index signatures use the same basic types.

## Concrete scalars

`middleware/types.json` owns the runtime mappings:

- `int32` / `int64`: `scpp::int_t<std::int32_t>` / `scpp::int_t<std::int64_t>`.
- `uint32` / `uint64`: `scpp::int_t<std::uint32_t>` / `scpp::int_t<std::uint64_t>`.
- `boolean`: `scpp::bool_t`. C++ reserves `bool`, so it cannot name this facade.

`middleware/tools/scalar_types.py` prelowers these into concrete public classes
and compiled implementations. Regenerate with `python3 -B middleware/tools/scalar_types.py`.
Middleware configuration and focused tests reject stale generated files.

Each scalar stores one private fixed native representation, without a heap
allocation, pointer or runtime object cast. Middleware materializes the registered
runtime value when invoking its constructors/operators and stores the resulting
representation. This is a value bridge, not a public alias to a native integer.
Size/alignment assertions check the runtime mapping. The public header uses
Clang's fixed-width representation macros to avoid a transitive standard-library
template dependency. Compiler code has no native-value accessor or implicit
conversion back to a native integer.

Construction from native literals is the bootstrap C++ input boundary; it uses
the selected representation's C++ parameter conversion followed by the runtime
constructor. It is not a checked literal-range validator. Cross-facade integer
conversions are explicit and preserve the runtime constructor's narrowing rules.
Same-type arithmetic, comparisons, prefix increment/decrement and compound
assignments delegate to runtime operators. Mixed-width or mixed-sign expressions
require an explicit conversion in this initial surface. This does not add a new
overflow policy: unsigned wrap and the runtime's signed arithmetic limits remain
in force. Division/remainder by zero retain runtime errors; failed compound
assignment leaves its left operand unchanged.

`boolean` supports contextual conditions and negation. Logical `&&` and `||`
remain built-in short-circuit operations; there are no eager overloaded logical
operators. Native `int` at `main`/host exit boundaries and native character pointers
for literals/argv remain explicit interoperability exceptions.

Collection sizes and indexes use `int64` consistently: `vector_string::size`,
`vector_json::size` and `json_value::size` match their `read`, `at` and `key`
arguments. Collection loops use signed counters without casts. Negative and
past-the-end indexes remain checked; native container sizes are range-checked
before conversion to the signed public size. Reservation capacities also use
`int64` and reject negative values.

Byte lengths, byte offsets, event counters and timings remain `uint64`;
configuration limits and byte values use `uint32`. This is the compiler middleware
contract, not a change to `std::vector` or the runtime's native storage types.

## Helpers and runtime semantics

Follow `../../../specs/count_empty_isset_contract.md`:

- `empty(string)` means zero bytes; `"0"` is not empty.
- `strlen(string)` returns byte length, including embedded NUL bytes.
- `isset` on a plain string, integer or boolean is true, including empty strings,
  zero and false. These concrete values have no absent/null state.
- Strings and scalars do not support `count`; scalars do not support `empty`.
- `count` stays on the planned common-symbol list, but has no overload in this
  slice because new vector/hash exposure is deferred. Nullable/keyed presence
  helpers must wait for their actual carriers rather than inventing behavior.

Filesystem adapters retain the existing startup behavior: return an owned string
or throw on failure. They do not pretend to expose the runtime's result wrappers
directly. Those wrappers remain a separate future boundary decision.

## Validation

`python3 tools/build.py middleware` explicitly rebuilds and publishes middleware;
`python3 tools/build.py test` builds only compiler consumers and runs focused tests.
No runtime change is required. Scalar tests cover widths, copies/conversions,
unsigned wrap, signed division/remainder, zero-divisor failures, short-circuiting,
string helper semantics and a timed 100,000-iteration allocation-free scalar loop.
Boundary probes check standalone headers, template exclusion and rejected native
escapes, integer truthiness, mixed signedness and unsupported helpers.

Local validation on 2026-10-26 (single trials, not performance guarantees):

| Check | Result |
| --- | --- |
| Explicit middleware rebuild/publication | 9.793 s |
| Compiler build plus seven focused tests | 3.165 s; all passed |
| Unchanged Ninja build | 0.006 s |
| Main translation-unit rebuild | 0.269 s |
| 100,000-iteration scalar sum loop | 684 microseconds; zero observed C++ allocations |
| Dependency isolation | Runtime/middleware edits caused no compiler rebuild; archives unchanged |

The scalar loop is a small boundary-cost check, not a full compiler performance
benchmark. Error construction and existing string/container allocations are not
included in its zero-allocation claim. Middleware was rebuilt explicitly; runtime
sources and packages were not rebuilt for this slice.

`string::is_one_of(const char *vocabulary)` performs exact, case-sensitive token
membership in a borrowed, NUL-terminated, space-separated vocabulary. Empty tokens
are ignored; an empty key never matches. It compares the runtime-backed string
bytes without constructing temporary strings or allocating. This is a middleware
utility for closed metadata vocabularies, not a new source-language builtin.
