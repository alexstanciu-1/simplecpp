# Lossless JSON document requirement
Doc Status: planning

Status: native lossless document API recommendation accepted by the user on
2026-09-22, with a PHP framework counterpart after native support is available.
Tracked upstream in [Simple C++ #240](https://github.com/alexstanciu-1/simplecpp/issues/240).
This remains a requirement, not an implemented framework API or language spec.

## Concrete compiler need

Manifest validation must reject an object where a list is required, including an
empty object and an object with numeric-looking keys. The selected decoder erases
these distinctions, as proved by `results/json-shape-preflight-01`. Runtime package
and preparation metadata also consume JSON schemas; duplicating a manifest-only
parser would not establish the shared boundary they need.

The preferred target-side option is a separate lossless document API on v0.1.
It can coexist with the current table-oriented json_decode contract. Changing
existing decode/encode behavior globally is not required by this migration.

## Required information and operations

- Parse one complete document with checked success/error; valid null/false are
  successful values, not failure sentinels. Failed parsing exposes no partial tree.
- Expose explicit null, boolean, number, string, array and object kinds, including
  empty containers. Keep nodes within an immutable document lifetime.
- Read ordered array elements and object members without numeric-string key
  normalization. A missing member must remain distinct from a present null node.
- Preserve decoded string bytes and exact object-key spelling after JSON escape
  decoding. Validate UTF-8 and surrogate pairs; do not normalize Unicode text.
- Preserve enough numeric information for explicit schema conversions without
  silently accepting fractional/out-of-range values as integers. A raw number
  spelling plus checked conversions is one possible design.
- Make duplicate-key behavior explicit. The adapter must be able to reproduce
  PHP's last-value-wins lookup and first-insertion key order where existing schemas
  rely on them; this need not dictate the document's internal representation.
- Expose parse failure category and byte position. The portability adapter needs
  to distinguish malformed syntax, malformed string encoding, invalid surrogate
  escapes and depth limits. Exact target/PHP message mapping requires a separate
  testable contract; do not equate all failures with missing configuration.
- Provide a nesting limit and explicit document/node lifetime rules. Do not expose
  raw owning pointers or require schema consumers to traverse arbitrary mixed data.

Concrete type/function names and storage design belong to the v0.1 JSON owner.
Typed document handles and node IDs are a viable direction, not a frozen API here.

## Acceptance witnesses

The saved preflight's eight documents must retain distinct node kinds and keys.
Add valid/invalid UTF-8, escaped keys, all JSON control escapes, paired/unpaired
surrogates, duplicate keys, null/missing/false, empty/nested containers, numeric
boundaries, trailing input, depth limits and repair-after-error behavior. Build and
run the PHS witnesses with strict/STAN on an immutable candidate.

After native support exists, implement the PHP counterpart and bounded converter
bindings, then migrate Manifest_Syntax as the first consumer. Compare its accepted
Project_Manifest values and rejected-input categories to the frozen adopted parser,
and run retained manifest/export/recovery and affected metadata regressions.
Native JSON availability alone does not mark any compiler parser portable.

## Alternative: shared portable parser

If selected by the user, implement a single portable-PHP reader with explicit typed
document records and the same PHP/native algorithm. Its owning location and loading/
conversion composition must be defined before coding; avoid separately maintained
PHP and PHS parser algorithms. The same acceptance matrix applies. This is a real
library implementation, not converter symbol inference or an escape-hatch payload.

The choice does not authorize new compiler language features, dropping object/list
validation, or modifying the legacy target in this v0.2 workspace. It is separate
from the separately accepted typed-cursor and semantic-enum decisions.
