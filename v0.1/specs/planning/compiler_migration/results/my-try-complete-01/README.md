# Complete my-try implementation conversion
Doc Status: supporting

The normal PHP-to-PHS converter successfully published all 29 implementation inputs
from the seven stage/compile directories. Storage types are real bindings; no fake
projection was used. Two host classes are explicitly omitted, and three traits are
expanded into their consumers. Boot/main/tests/samples/PHP collection implementations
remain outside the converted input, as documented host/runtime boundaries.

Published artifact: compiler/my-try/build/portability-01/phpp (repository-relative).
Source snapshot and hashes sit next to it; source and output fingerprints were
verified against the live implementation and converter publication manifest.

## Recorded commands/results

```text
php tools/php_portability/convert.php compiler/my-try/build/portability-01/source compiler/my-try/build/portability-01/phpp --stats
converted=29, reused=0, removed=0
(repeated unchanged)
converted=0, reused=29, removed=0
php tools/php_portability/check.php compiler/my-try/build/portability-01/source
checked=29
```

The production diagnostic snapshot tool is:
`php compiler/my-try/tools/conversion_probe.php NEW_DIRECTORY`.
It now defaults to real source; the older fake mode requires --fake-storage and must
never be used for a published compiler. For a fresh atomic conversion, use its
source/ snapshot with the ordinary convert.php CLI and a separate output directory.

## Behavior and boundary proofs

Passed PHP lint plus compiler storage/tokenizer/AST/model/LLVM-text tests.
The LLVM source-pipeline fixtures/rejections passed; all 19 emitted LLVM files were
byte-identical before and after checked-payload adaptation.

Passed portability storage_bindings.php, object_hashes.php, object_casts.php,
compiler_syntax.php, run.py and check.py, and generated-global-facade consistency.
Native pre-tokenizer regression fixtures passed. Earlier signature/static-field
proofs passed during this same work; they were not rerun without a relevant change.
No stale PHP SplObjectStorage or fake collection identifiers appear in published PHS.
The two host worker declarations do not appear in published output.

The object-cast proof generates and inspects C++ from a small converted fixture:
correct object_is/checked_object_cast calls, polymorphic interface, no swallowed
instanceof type. Native hash TypeMapper output was inspected too. These are
source-generation checks, not C++ compilation or execution.

## Limits

No native compilation was performed. The configured native target was not changed.
A native build needs the Storage source-binding delivery together with the new
native checked-object support. Whole-program typing, exception transport, nullable
boundaries, cross-file metadata, layout and runtime behavior remain for that next
stage. This checkpoint completes PHP-to-PHS conversion only. It does not declare a
native compiler release ready. No performance claims are made from this pass.
