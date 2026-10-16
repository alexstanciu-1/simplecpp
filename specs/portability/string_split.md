# Typed string splitting
Doc Status: supporting

Use `q_explode(string $separator, string $string): array` with explicit
`vector<string>` result intent. Exactly two arguments are supported; the converter
emits `explode($separator, $string)` and the native builtin returns a string vector.
The [builtin contract](../builtins/string/explode.md) owns splitting semantics.

```php
$parts /** vector<string> */ = q_explode('=', $definition);
```

Splitting uses the entire separator byte sequence, consuming nonoverlapping matches
from left to right. Preserve leading, trailing and repeated empty parts. Empty input
with a nonempty separator produces one empty string; an absent separator produces
one unchanged string. NUL, invalid UTF-8 and multibyte separators are supported.
No normalization, filtering, trimming or input mutation occurs. Source literals
remain subject to the existing converter restrictions; construct binary data at
runtime with `string_byte_from_int` where necessary.

The function map binds directly to PHP `explode` and native `php::explode`. The
ordinary generated facade exposes the first two required string parameters and
array return; no host adapter, parser special case or runtime algorithm is added.
The converter checks arity, not remote operand types. Native typing rejects
incompatible operands. PHP coercion behavior outside string inputs is not part of
this portable contract. Three-argument limit forms remain outside this slice.

An empty separator throws PHP `ValueError` or native `scpp::ValueError`. The portable
exception policy does **not** expose `catch (\ValueError ...)`. Separate host and
raw C++ probes establish those builtin errors, not portable catch or uncaught-exit
parity. Both migrated compiler calls use fixed nonempty separators. This slice does
not extend the exception bridge.

Only `Runtime_Build_Contract::definition_name()` and `validate()` migrate, with the
private `split_byte()` removed. Full splitting is essential: `NAME=1=2` must retain
three parts and fail validation. Diagnostics, validation order, encoding and build
requirement merging retain their existing behavior.

Both implementations materialize owning strings and a vector. The new native path
uses string search/substrings instead of the compiler's per-byte wrapper loop.
No speed, allocation-count or memory reduction is claimed without measurements.

Run the focused proofs with fresh evidence directories:

```sh
python3 tests/portability/string_split.py --results /tmp/FRESH_SPLIT_RESULTS
python3 tests/portability/string_split_compiler.py --results /tmp/FRESH_SPLIT_COMPILER_RESULTS
```

The second proof rebuilds the compiler, executes exact build-contract assertions
inside PHP and native drivers, compares emitted artifacts and encoded build requirements,
and builds/runs a custom runtime provider consumer using those declared inputs. It accepts
`--resume` after a failed attempt, retaining separate logs. The probes use the shared
runner, bypass legacy STAN and leave LLVM parked. They are not a cumulative sweep.
The split review exposed a missing-hash-key `??` lowering issue in native
`Output_Publication`. The subsequent [local hash coalescing slice](hash_coalesce.md)
repairs and proves that path. The split-specific emission proof remains separate
from the native publication lifecycle proof.
Dated evidence lives in the
[conversion-quality plan](../../compiler/my-try/docs/planning/conversion_quality.md).
