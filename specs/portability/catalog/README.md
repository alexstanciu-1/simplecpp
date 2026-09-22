# Strict Simple C++ portability catalog
Doc Status: planning

Snapshot: 2026-09-21. Purpose: inventory the strict target before expanding our
small PHP portability tool. This catalog is a discussion/planning aid, subordinate
to the linked semantic specs; it does not add language or library support.

1. [Language features](language.md): 59 feature families, target status, PHP
   authoring direction, local conversion requirements and converter status.
2. [Libraries](libraries.md): all 241 strict registry entries, grouped by family,
   with source spelling, proposed PHP spelling and native lowering provenance;
   additional type/member/constant families are called out separately.
3. [Conversion decisions](conversion.md): examples, representation choices, runtime
   support boundaries, builtin-name collisions and suggested implementation order.

Current converter readiness: [consolidation/debt](../debt.md).

## Framework scope

The inventory includes target capabilities we do not intend to implement in PHP.
GUI/WebView is out of scope; dynamic PHP behavior is discouraged. Grow the framework
as concrete compiler needs arise. The [native escape hatch](conversion.md#8-incremental-scope-and-native-escape-hatch)
is an accepted future option, not implemented converter functionality.

## Coverage and evidence

The language inventory covers the documented strict PHP++ surface by family.
The library table covers every name in the pinned strict registry exactly once,
including internal entries so they cannot silently become proposed public APIs.
It is not a claim of every C++ header member being available from PHP++.
Constants and full member/overload signatures are not centrally enumerated by
that registry; these gaps are recorded explicitly and require per-family intake.

The strict contract metadata is a compiler-consumer normalization foundation.
Missing/blocked entries there are not evidence that the current S2S rejects those
calls. Optional modules, platforms and narrow overloads retain their own contracts.

Only the [first converter slice](../first_slice.md) is implemented. Other PHP
spellings in this catalog are proposals, not accepted syntax or installed shims.
The compiler prototype's generic/LLVM capabilities are not counted as current
Simple C++ S2S features.

## Documentation discrepancies discovered

- `specs/php/catalog.md` says exceptions are unsupported; the dedicated active
  `generators/php/specs/exceptions.md` specifies a supported subset. Record this
  conflict; use the focused contract for planning, rather than copying the stale
  summary into a new rejection rule. The source docs still need consolidation.
- `generators/php/specs/unsupported.md` says foreach outside vector is rejected;
  the top-level array contract and current strict guidance include hashes and
  approved wrapper success payloads. Higher semantic authority takes precedence.
- The same unsupported list rejects reference capture while normalized generator
  rules describe `use (&$x)`. Capture coverage needs a focused target proof before
  the converter promises it; this catalog does not silently reconcile the conflict.
- Runtime catalog string summaries describe codepoint strlen/strpos, whereas
  dedicated builtin contracts and strict quick-learn specify byte behavior.
  Use the specific builtin contracts for proposed adapters, and include non-ASCII
  witnesses before freezing string mappings.
- General include guidance differs across old module docs and strict project
  guidance. Prefer a separate PHP loader and the target project source graph;
  do not build runtime include emulation into portability conversion.
- Metaprogramming is explicitly a future contract. Concrete prototype progress
  does not make arbitrary template authoring available in today's S2S.

These findings are inventory debt, not changes to the owning language contracts.
