# Runtime-preparation symbol spelling
Doc Status: supporting

`src-runtime-preparation/symbols.php` is the first ready file from the adopted
runtime-preparation implementation. It retains complete ordered identities and the
existing reversible LLVM/C spelling: `rp_` prefix, `_X_` component separators,
doubled literal underscores and uppercase `_xHH_` escapes for every non-alphanumeric
byte. Empty components remain valid; an empty component list does not.

A scalar byte loop replaces the regex callback/sprintf implementation. No locale,
Unicode character classification or hashing participates. Append uses the same
component encoder, and arbitrary input bytes remain reversible. This changes no
link names, native package format, compiler feature or provider identity policy.

## Explicit PHP carrier validation

`sequence_require_strings($items, $shape_error, $element_error)` validates the PHP
carrier against an explicitly declared `vector<string>` boundary. PHP rejects a
non-list or a non-string element with the supplied InvalidArgumentException text.
The native helper accepts a typed string vector and has no runtime checks: shape
and element representation are already fixed by that signature. It does not infer
container intent, validate arbitrary native mixed values or impose hot-path policy.

Symbols supplies its existing diagnostic text, preserving invalid PHP calls as
well as valid identity spelling. The method's separate nonempty-list check runs
on both implementations. No claim is made that arbitrary native implicit casts
have PHP's strict_types behavior; authored callers must supply typed strings.

The native helper belongs to the framework's `collections.phs`, with independent
assembly ownership. Runtime preparation's standalone bootstrap loads the PHP
framework without loading a compiler session or executing a stage. This is host
composition, outside the converted implementation source selection.

## Proof

The frozen original matches 1,408 identity inputs and 144 append cases: binary
bytes, delimiter-like strings, Unicode, deterministic random tuples, ordering,
empty components, and invalid PHP carrier/element diagnostics. Inputs are unchanged.
The native witness independently expects representative names, append equivalence,
all 256 encoded byte values and the empty-list error.

The retained runtime-preparation integration suite exercises its own independent
symbol decoder, package production/reuse and native consumers. The full preparation
implementation, metadata schemas, type adaptation and Clang orchestration remain
unmigrated; one portable symbol module does not imply pipeline portability.

Evidence: `specs/planning/compiler_migration/results/preparation-symbols-01/summary.json`.
Strict native/STAN, independently expected PHP/native output, framework/converter
regressions and twenty-one retained compiler fixtures pass on
`2f0d667f38a35ff02ef77e813f409189cba2d032`. The retained preparation integration
suite passes all 170 checks, with its report/logs recorded beside the cumulative
evidence. Thirty-six production files are ready.
