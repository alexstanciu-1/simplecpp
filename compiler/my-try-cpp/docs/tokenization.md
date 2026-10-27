# Compact source tokenization
Doc Status: supporting

## Storage and ownership

Each token is exactly eight bytes (size and alignment asserted in middleware):
32-bit byte offset and a 32-bit word holding a 24-bit length plus an 8-bit tag.
Tag zero is reserved. Values 1–125 classify six general categories, 25 single-byte
punctuation spellings, 30 multi-byte spellings and 64 parser-recognized words.
See [the inventory](token_inventory.md) and
[the explicit tag enum](../middleware/include/scpp_compiler/token_kind.hpp).
There is no stored EOF token. Collection count defines the end.

The source buffer may contain at most 4,294,967,295 bytes. Tokens must have lengths
1–16,777,215 and fit within their source. Builder appends must be ordered and
nonoverlapping. Bounds are checked before packing. Numeric signs, escapes and
literal values are not decoded here. Tags do not make contextual words reserved
identifiers in future parser positions.

`token_sequence` retains an immutable source snapshot and a contiguous runtime
vector of compact tokens. Copying its handle shares both bytes and tokens; it
does not copy token spelling. `token_builder` owns private mutable construction
and transfers the vector on finish. A prior generation's token count is a bounded
reserve hint; initial construction reserves up to 256 slots and grows geometrically.
Capacity is deliberately not shrunk with another allocation/copy at publication.

Old generations survive new reads, tokenization and model reset while consumers
retain handles. No source concatenation, token-index remapping or syntax compaction
is introduced. Parser-level identity/reuse remains outside this slice.

## Scanner and phase ownership

- `src/tokenize/scanner.cpp`: single-pass lexical scanning and source-byte errors.
- `src/tokenize/spellings.cpp`: allocation-free word classification and first-byte
  punctuation dispatch, inspecting at most two subsequent bytes for operators.
- `src/tokenize/stage.cpp`: revision selection and transactional batch publication.
- Middleware: concrete storage, checked byte access, packing, runtime task dispatch
  and profiling; compiler include closure contains no active templates.

The scanner consumes already-loaded bytes. `peek()` returns -1 outside the source,
keeping actual NUL bytes distinct from EOF. `matches()` compares borrowed literal
spelling without substring allocation. No regex engine, token object allocation,
per-token string copy or preliminary counting pass is used.

Existing rules are preserved: ASCII identifiers, whole variable spans, decimal
numbers/exponents, whole single/double-quoted strings, comments omitted from syntax
and longest recognized punctuation. Angle pairs remain separate tokens for generic
syntax; a future parser may recognize adjacent pairs as shifts, as my-try did.
All spans remain byte-based, not Unicode code-point positions.

Initialization now performs scan → checked load → tokenization before publishing
the static model. Later operations are separate coordinator-only calls:
`rescan_sources()`, `load_sources()`, `tokenize_sources()`.
Tokenization rejects unread current revisions. The tokenized revision is distinct
from the loaded revision and downstream pending flags. Repeated tokenization of
current generations does no work, including when flags still say added/changed.
Deleted records retain old tokens but are skipped. Notification invalidates a live
revision even if previous changes remain pending.

Each file's worker builds a private generation. Only a successful whole batch swaps
results into the inventory and advances tokenized revisions. A lexical error keeps
all old token generations and revisions; already-published loaded source bytes may
be newer. The old tokens retain their own matching old source snapshot. Errors include
the relative source path and byte offset. Full module qualification for identical
relative paths in multiple roots remains a diagnostic improvement opportunity.

## Validation

Twelve focused tests pass locally and on dev002. They cover independent expected
tags/spans, quote/comment distinctions, EOF and embedded NUL, bounds, exact 24-bit
length acceptance/rejection, reserved zero, immutable generations, unread revision
rejection, no-op reuse, forced invalidation and all-or-none lexical-failure recovery.
The separate ASan/UBSan configuration also passes with leak detection. This is not
a ThreadSanitizer or semantic-compilation claim.

The opt-in corpus comparison executes the authored old PHP tokenizer through its
shared runner and compares every token offset and length on all three samples.
New tags are independently classified from those spans. dev002 verifies identical
source hashes and complete token dumps at one and 16 jobs against that reference.
[Validation results](../samples/token-validation.json):

| Sample | Files | Tokens | Exact payload bytes |
| --- | ---: | ---: | ---: |
| Small accounting | 15 | 19,469 | 155,752 |
| Large accounting | 93 | 86,942 | 695,536 |
| Converted compiler | 257 | 298,589 | 2,388,712 |

No parser, semantic analysis or generated-program execution is claimed.

See [dev002 measurements](../samples/tokenization-dev002-16.md) for all three projects.

## Reproduction and performance boundaries

```sh
python3 compiler/my-try-cpp/tools/build.py middleware
python3 compiler/my-try-cpp/tools/build.py test
ninja -C compiler/my-try-cpp/build/compiler -j8 tokenization-profile tokenization-allocations
python3 compiler/my-try-cpp/tools/validate_token_samples.py
python3 compiler/my-try-cpp/tools/verify_token_samples.py
python3 compiler/my-try-cpp/tools/measure_tokenization.py --jobs 16 \
  --output compiler/my-try-cpp/samples/tokenization-dev002-16.json
```

Run the final command on the documented dev002 workspace for that host's baseline.
Normal timing, gated ready-worker timing and allocation counters run separately.
Source discovery/loading completes before tokenization timing. Whole-process memory
still includes those stages. The benchmark records VmRSS and VmHWM from this exec's
`/proc/self/status`; getrusage can retain the Python launcher's peak across exec.
Payload and vector-capacity bytes are exact; RSS and cumulative allocation requests
are different measures. Allocation interception covers ordinary new/new[], not
malloc, aligned allocation or allocator overhead. No-op reuse is timed in-process
and checked for zero allocation requests.

Gated metrics reuse the opt-in runtime launch gate from read profiling. Worker-body
and scanner-callback CPU are reported separately. Queue and completion-lock sums
overlap wall time; do not add them to total duration. Publication happens after
joining, with no token publication mutex. Thread creation, work, completion, joining,
result collection and model publication remain separate diagnostic intervals.

The first implementation keeps runtime-backed scalar operations and bounds-safe
source access even in the hot loop. Middleware crossings and geometric capacity
slack are measurable follow-up opportunities, not reasons to weaken safety or
move the compiler grammar into middleware before evidence warrants a change.
