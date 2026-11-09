# First string compiler slice

Doc Status: supporting

The [source contract](../../../specs/compiler_scalars_v0_2.md#first-string-source-slice)
owns the language behavior. Default-runtime metadata now activates `string` and
generates its canonical `type_string` definition and registration. The generator
validates the existing runtime string representation and count contract. There is
no runtime metadata interpretation by the compiler.

```text
$message string = "hello\0world";
$copy string = $message;
echo $copy;
return 8;
```

The equivalent cpp and js declarations use `string message = ...;` and
`let message: string = ...;`. This works inside entry and typed-function bodies.
A written string destination accepts only string values; text conversions are not
activated even though the native runtime already implements some of them.

## Ownership and structure changes

- `ast_string_expression` moves from `structures/output.php` into `structures/strings.php`.
  Its prepared facts now include canonical type identity as well as decoded bytes.
  Empty bytes are valid; null means undecoded. Reset clears both type and bytes.
- The scalar constant union remains `int|float|bool|null`. Strings leave its known
  flag false and payload null. This flag describes scalar cache availability, not
  whether the string literal has been prepared. Neither locals nor references copy
  literal bytes into their scalar facts.
- `String_Literals` replaces the output-specific decoder name; it owns the same
  escape validation for both local initializers and direct output. Diagnostic
  positions stay unchanged; wording now says string instead of echo.
- Generated `type_string` dispatches decoding and emits length-aware
  `scpp::string{literal, length}`. `Cpp_String_Literals` supplies shared byte spelling
  for construction and direct echo, using fixed-width octal escapes.
- `type_definition::cpp_headers()` supplies storage requirements, separately from
  conversion headers. Only actual string locals require `scpp/string.hpp`.
- No new prepared-value hierarchy, dependency graph or duplicated bytes are added.
  The existing declaration link and identity conversion represent local copying.
  Generator inventory version is 6; CMake tracks the new generated definition.

## Native behavior

String locals use the existing owning `scpp::string` public API. Same-type copying
uses its copy constructor; automatic local destruction handles lifetime. Embedded
NUL is preserved through construction and output. The emitter only includes public
runtime headers. Standard string storage remains private to the runtime.

Direct literal echo remains allocation-free and requires only the output API.
String-value echo binds the value once before reading `data()` and `size()`. A
returned string remains alive across both queries; calls execute exactly once.

## Explicit debt

String globals remain rejected until initialization order and lifetime are designed;
current scalar globals use `constinit`. String equality now uses [generated handlers](string_equality.md).
Named interpolation uses generated scalar casts and ordered runtime appends. See
[the flavor spellings and boundaries](../../../specs/string_interpolation.md#active-v02-flavors).
General interpolation expressions and uninitialized locals remain deferred. Native runtime support does not imply compiler admission.
The broader echo formatting/error contract remains tracked in the existing echo debt.

## Verification

`tests/strings.php` checks all flavors, exact UTF-8/NUL/escape bytes, empty strings,
copy bindings, pure lowering, scalar-cache separation, reset/repreparation, rejected
conversions/globals, and incremental body edits retaining the unchanged caller AST.
`tests/strings_native.py` builds and executes all three flavors and verifies exact
stdout and exit status. Test-only native mutation clears the original and verifies
that the copy remains intact; this does not add source method syntax.

On 2026-11-04 the native proof used Clang `-O2 -g0`, libc++, Ninja `-j8`, mold,
no PCH/LTO, fresh instrumented application objects and the prebuilt runtime archive:

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link command ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 197.753 | 23.774 | 26.044 | 8.838 | 13.135 | 61 |
| php | 115.179 | 10.384 | 8.671 | 2.912 | 4.401 | 55 |
| js | 118.991 | 12.088 | 9.195 | 3.032 | 4.700 | 54 |

These are individual correctness builds, not repeated-run performance estimates.
Trace stages are nested and must not be summed. PHP fixture generation, including
semantic assertions, took 51.475 ms for the original native proof. Raw traces,
commands and results are in `v0.2/build/strings-proof-2026-11-04/` (untracked).
Runtime generator parity retained identical generated artifacts and rejection
contracts across 17 accepted and 37 rejected fixtures; only metadata input hashes
changed in the baseline.

## Metadata-declared byte functions

The default string provider publishes `strlen(string): int`. Its generated binding
`scpp::str::byte_length(const scpp::string&)` forwards to the existing stored byte
count. Embedded NUL contributes one byte and UTF-8 contributes its encoded byte length.

Preparation first watches the ordinary source function slot. When the slot is empty,
the generated runtime registry supplies its immutable fixed-arity signature, binding and
provider header. A local source definition can shadow it and deletion restores the
runtime target. The existing call AST keeps either that signature or the stable source
symbol. Lowering evaluates the argument once.

The same registry publishes `string_byte_from_int(int): string`. Its binding takes
a canonical int64 by value and constructs an owned one-byte string, including for
0 and 255. Values outside 0..255 throw native `std::invalid_argument` with
`Byte value must be between 0 and 255`. The function accepts an exact integer
argument through the same runtime call path as `strlen`.

PHP free functions and JS/C++ receiver methods select the same native operation
through generated signature keys. Preparation checks the receiver and positional
arguments once. Calls preserve source evaluation order when an argument can have
side effects. All searches count bytes. JS and C++ return −1 on a miss. PHP
`strpos` and `strrpos` declare `result_or_false<int>` in their flavor signatures.
The generated body calls the byte search once and wraps a hit, including offset zero,
or returns the false sentinel. An inferred PHP local keeps that carrier; `isset($pos)`
tests for a payload. This slice supports the two-argument forms. ASCII case conversion preserves all other bytes. Predicates
and searches view the existing string storage and accept embedded NUL. Repeat
checks its signed count and output size before allocating.
See the [three-flavor catalog](../../specs/planning/non_object_feature_catalog.md#classic-string-functions).

## Explicit text conversion

The string provider publishes explicit formatters in runtime metadata. Compiler
generation uses these rows for cast permission, lowering and provider includes.
The boxed generator selects the same named bindings by stored kind. Preparation
records the ordinary conversion code on the cast expression. The default runtime
formats finite floats and integers with `to_chars` into a stack buffer and copies
the resulting bytes into the owning string. See [the scalar contract](../../../specs/compiler_scalars_v0_2.md#explicit-scalar-to-string-casts-in-v02).
