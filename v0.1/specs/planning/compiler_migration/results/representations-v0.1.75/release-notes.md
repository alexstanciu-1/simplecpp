# v0.1.75 release notes
Doc Status: historical

Copied from the verified release checkout CHANGELOG.md.

## 0.1.75 - 2026-09-09

### Additions

- Added string and ordinary class fields in value structs, plus vector/hash/fixed-array compositions of supported field types. Struct copies preserve string/container values and share referenced class objects; `mixed`, `dynamic`, and managed union payloads remain excluded.
- Added compiler-support runtime APIs for source buffers and byte spans, source line indexes, fixed-width and enum hash keys, enum conversion/name helpers, vector reserve/capacity/compact/resize/fill operations, stable numeric hashes, and microsecond/nanosecond monotonic timers.
- Added explicit byte, UTF-8 codepoint, and grapheme string APIs; string-parts and contiguous text builders; and native row-arena, bitset, work-queue, little-endian binary-codec, and memory-accounting helpers.
- Added typed PHS/JSS runtime tokenizers and a compact token buffer with location accessors, whitespace/newline flags, and extended-length storage. Expanded scanner support for operators, keywords, decimal numbers, and multiline strings.
- Added runtime metadata exports and strict symbol-contract overlays for scalar types, operators, conversions, helpers, containers, and native ABI bridges, including integer/text/string bridges and `vector<int32>` bridge metadata. Metadata distinguishes available runtime support from source-consumption paths that remain blocked.
- Added dependency-scoped project-unit headers, source/dependency summaries, module public-surface validation, and build grouping policies. Grouped compilation is opt-in; module dependency/public API policies support report, warn, and fail modes.
- Added separate debug/release build roots, local object-action caching, reusable build-planner/source-inventory state, Ninja explain provenance, Clang time traces, and build-invalidation benchmarks.
- Added persistent STAN workers, semantic reuse for warm and method-body builds, and separate blocking/advisory analysis phases.
- Added reusable task worker pools, configurable pool sizing, ordered batch publication, publication batch caps, and task timing/lock diagnostics. The tasks module remains opt-in and experimental.
- Added const parameter support and struct member-access usability improvements.

### Fixes

- Fixed the long chained-string-concatenation transpiler hang reported in #214. This fix was already merged into main after v0.1.74 but had not appeared in a published release.
- Made JSON parse and encode failures recoverable through checked results, so a process can reject malformed input and continue with a later request (#226).
- Fixed decoded JSON conversion at required typed boundaries: invalid integer payloads and missing required values are rejected, and decoded arrays can initialize typed vectors recursively. Explicit source casts retain their coercing behavior.
- Restored missing-runtime-module errors in the fast STAN build gate, including JSS curl helpers; cached analysis preserves the same rejection.
- Restored Clang debug runtime-error source locations by preserving header/template debug information through precompiled headers.
- Fixed `scpp runtime-build` to build requested optional module artifacts even when the shared base runtime already exists.
- Corrected compiler-specific diagnostic test expectations and excluded mysqli-dependent native tests when the module is disabled.
- Separated STAN model-incomplete receiver/member findings from confirmed build-blocking source errors (#225), while preserving explicit enum, fixed-width integer, and other supported discipline checks.
- Fixed stale generated artifacts, unstable scoped-header hashes, unnecessary generated timestamp changes, and repeated dependency-summary work that increased rebuild fanout and warm-build cost.
- Fixed Ninja output draining to avoid deadlocks on large build output.
- Guarded the Unix memory-probe header so Windows builds do not require `sys/resource.h`.

### Breaking Changes

- PHP++ strict and legacy profiles now expose `json_decode` as `result<mixed>` and `json_encode` as `result<string>`, replacing their direct dynamic/string returns. JSS `json.decode` and `json.encode` follow the same checked contracts. Callers must unwrap with `take(...)` before consuming the value.
- JSON native decode/encode entry points and return contracts changed. Rebuild runtime artifacts and native consumers; old artifacts cannot satisfy the new checked decode/encode symbols.
- For consumers of earlier unreleased compiler-runtime branch snapshots, native token-buffer storage has changed: kind ids are `uint16`, normal lengths are `uint16` with an extended-length side table, and line/column values are derived from line-start offsets. Native `phs_tokenize_count` and `jss_tokenize_count` convenience functions were removed; use `token_buffer_count` with the corresponding tokenize-buffer call. Direct native field consumers must adapt.

### Migration Notes

- Follow [the JSON migration guide](docs/json_builtins.md#migrating-from-0174) for PHS and JSS examples. Parse/encode failures return the error branch; source `catch(Exception ...)` is not their recovery boundary. Valid JSON `null` and `false` remain successful values, and typed field conversion retains its separate runtime contract.
- Run `scpp run --force` to rebuild each consuming project and its runtime after updating; use `scpp run --mode=release --force` for release builds. Rebuild and deploy matching runtime/module artifacts for native or packaged consumers.
- Prefer token-buffer accessors over native column assumptions. Location accessors now derive positions; review workloads that repeatedly request every token's location.
- Treat build grouping, object caching, module policies, and task diagnostics according to their documented opt-in/default settings. This release does not add remote caching/execution or claim complete source consumption for every exported metadata family.
- Repo-local PHP++ and JSS Agent Skills were reviewed for this release candidate. JSON examples now unwrap checked results; adjacent stale error declarations and helper names were corrected.

### Known Limitations Retained From Earlier Versions

- Explicit top-level `return 0;` still fails compilation; let a successful script complete normally instead.
- Native nullable-integer prefix/postfix increment and result/error-sentinel equality remain incomplete. Extract and update a checked integer, or inspect result state explicitly with `has_error()` / checked `take(...)`.
- Mixed bool/int loose equality remains under contract review because runtime behavior and the native test disagree. Prefer explicit type stabilization or source strict identity (`===`). Namespace-alias imports also remain unsupported.
