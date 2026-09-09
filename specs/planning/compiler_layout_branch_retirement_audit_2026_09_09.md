# Compiler layout integration branch retirement audit
Doc Status: planning

Date: 2026-09-09

This is an evidence-based branch retirement assessment, not semantic authority or release approval.

## Decision

No unported feature commit was found on `compiler-v0.1.74-layout-integration`.
Its work was replayed into the history now reachable from `codex/compiler-vector-runtime`.
No wholesale merge or cherry-pick is recommended based on this audit.
The user authorized retirement after reviewing this audit. On 2026-09-09,
the annotated tag `archive/compiler-v0.1.74-layout-integration` was published
at the audited old tip, atomically with deletion of the GitHub branch.
There was no local branch to delete. The archive tag preserves the original
commit lineage; a commit mapping alone would not preserve the Git objects.

The source/runtime behavior is not identical in every respect: later tokenizer
changes deliberately replace old native storage and convenience entry points.
Those differences are documented below instead of being counted as missing features.

## Revisions and method

- Old branch: `1a1f2342b73c104571a83a6cce98ca5d2cb2eb0d`.
- Candidate: `fc20d73d040c4e69758bcec0b1caf40c26755f72`.
- Common ancestor: `5e0a5b57d41e8a164939d392e397d5c620307693` (`v0.1.72`).
- Old tip dated June 27; its VERSION.txt is 0.1.72 despite the branch name.
- Candidate VERSION.txt is 0.1.74.
- Old branch has 44 commits not reachable from the candidate: 43 non-merge commits and one merge.
- All 43 non-merge subjects have a unique corresponding candidate-history commit.
- Stable patch IDs match for 29 pairs. The remaining 14 pairs were inspected with per-pair `git range-diff`.
- Nine of those 14 have identical added/deleted lines with different context; two are indentation adaptations; one adapts integer-wrapper spelling; two reuse newer integer/enum lowering owners.
- Old branch changes 107 files relative to the common ancestor: 74 are byte-identical at candidate HEAD, 33 differ, zero are missing.
- Every registration/alias in the old strict and legacy runtime symbol JSON catalogs remains present with the same value in the candidate catalogs. This does not imply every runtime signature is unchanged.
- A merge preview used a temporary Git object directory and did not touch the checkout or refs. Merging old branch into the candidate reports conflicts in 30 files.
- The audit itself performed no source edits or Git mutations. The subsequent
  authorized tag creation and branch retirement are recorded above; no merge occurred.

The old merge `4fceec93` merges `v0.1.72` into the old history. That release is
already an ancestor of the candidate. The final mapped feature commit is
`2deae046` (July 1 commit date), corresponding to old tip `1a1f2342`.
At that import point, 94 of the 107 contribution files were identical; the other
13 differed for the newer compact-layout/UI base and header integration.

## Feature preservation

| Area | Finding |
| --- | --- |
| Typed hashes, fixed-width and enum keys | Runtime hash implementation and original positive/negative source fixtures retained. |
| Fixed-width enums and conversion/name helpers | Present. Storage mapping lives in newer enum lowering; duplicate/raw-integer checks remain. |
| Vector capacity | Original reserve/capacity/clear/compact helpers retained; resize/fill added later. |
| Source buffers and spans | Original byte/span/release contract retained. Take now moves string storage instead of copying then clearing. |
| Source line indexes | Implemented and exercised by the retained source test. |
| Byte/UTF-8/grapheme APIs | Original helpers and source tests retained. |
| Row arena, bitset, work queue | Implementations and native tests byte-identical. |
| Binary codec | Implementation and native test byte-identical; little-endian/truncated-input assertions pass. |
| Memory accounting and stable hashes | Implementations and native tests byte-identical. |
| String parts builder | Original implementation retained, native/source tests pass; contiguous text_builder added later. |
| High-resolution monotonic timers | Runtime implementation and original test files byte-identical; tests pass. |
| Build metadata, Ninja explain, PCH ordering | Original entry points and behavior retained in the expanded build process. |
| Typed tokenizer | Public accessor family retained; native representation and some lexical behavior intentionally changed. |

## Differences requiring explicit awareness

### Tokenizer native compatibility

Commit `60c565b5` (Prepare compiler metadata contract runtime support) changes:

- Kind storage: uint8 to uint16.
- Normal length storage: uint32 to uint16, with an extended-length side table.
- Per-token line_numbers and columns vectors: removed; locations derived from line_start_offsets.
- Flags: previously zero; now whitespace-before, newline-before, and extended-length flags.
- Native `phs_tokenize_count` and `jss_tokenize_count`: removed.
- Internal `detail::tokenize_ascii_language`: removed.

Equivalent count calls are `token_buffer_count(phs_tokenize_buffer(source))`
and `token_buffer_count(jss_tokenize_buffer(source))`. Both convenience functions
only occur at their definitions when searching the old repository: no catalog,
doc, test, or in-repository caller was found. External native consumers were not
inspected, so this audit does not promise native source/ABI compatibility.

The current token-buffer spec documents the new field widths, flags, and derived
location accessors. Public source signatures remain available, and the original
typed-accessor PHS test passes. Later changes also add keywords/operators, decimal
number scanning, and multiline string scanning; the original tokenizer is not an
identical behavioral snapshot.

There is a performance tradeoff visible in the current implementation:
`line_for_offset` and `column_for_offset` scan line-start entries, whereas the old
stored per-token coordinates allowed constant-time lookup. `token_length` scans
the extended-length table when needed. These are code-derived complexity
observations, not measured regressions. If a compiler consumer repeatedly asks
for every token's location, assess that access pattern separately rather than
merging the old tokenizer wholesale. The mixed adapter uses a moving line index.

### Integer/enum patch differences are not lost behavior

The old fixed-width initializer cast is subsumed by the current
`wrapExprForExpectedType` integer-to-integer cast rule. The old
`enumExplicitStorageType` method is replaced by the mapping inside
`enumStorageType`. `emitEnumClass` still calls `validateEnumCases`, which parses
backed case values and rejects duplicates. The enum source positive and negative
tests pass. The candidate also recognizes cross-file declaration kinds.

### Release issues remain separate

The Unix-only `sys/resource.h` include responsible for the previously observed
Windows CI failure exists in the same byte-identical memory-usage header on both
branches. The old branch offers no fix for it. Later JSON result-wrapper changes
also remain in the candidate and need release migration notes. Retiring this
branch would not resolve those release tasks.

## Validation

On Linux, at the candidate revision above:

- 18 original source test entries: PASS (11 legacy-profile entries, 7 strict-profile entries).
- 3 curated runtime entries: PASS.
- 8 freshly configured/built native CTest executables: PASS, Debug build with assertions enabled.
- `php tests/tools/test_scpp_stan_strict_discipline.php`: PASS.

Total: 30 focused checks passed. Expected-negative source tests count as passing
when the required rejection occurs. The vector source/runtime and source-buffer
runtime tests contain later additions; the other original fixture sources are
unchanged. These checks establish targeted preservation, not a full cross-platform
release gate or a proof for every possible external consumer.

Commands:

```bash
php tests/tools/run_tests.php run --suite=php --profile=legacy --test=<id> --jobs=1
php tests/tools/run_tests.php run --suite=php --profile=strict --test=<id> --jobs=1
php tests/tools/run_tests.php run --suite=runtime --test=<id> --jobs=1
php tests/tools/test_scpp_stan_strict_discipline.php
cmake -S runtime -B /tmp/simplecpp-layout-audit/native-build -G Ninja -DSCPP_WITH_MYSQLI=OFF -DCMAKE_BUILD_TYPE=Debug
cmake --build /tmp/simplecpp-layout-audit/native-build --parallel 4 --target test_row_arena_t test_bitset_t test_work_queue_t test_binary_codec test_memory_accounting test_stable_hash test_string_parts_builder test_datetime
ctest --test-dir /tmp/simplecpp-layout-audit/native-build --output-on-failure -R '^scpp_test_(row_arena_t|bitset_t|work_queue_t|binary_codec|memory_accounting|stable_hash|string_parts_builder|datetime)$'
```

Temporary detailed logs and machine-readable evidence are in
`/tmp/simplecpp-layout-audit/`. Initial attempts to run the seven strict fixtures
with the default legacy profile selected no tests; those attempts are excluded
from the counts. All seven were subsequently run with `--profile=strict` and passed.

## Complete commit mapping

| Old commit | Candidate-history commit | Subject | Assessment |
| --- | --- | --- | --- |
| `2abda9c6` | `8ed8b6ac` | Export runtime metadata from runtime build | Identical stable patch ID. |
| `4097d1ab` | `30202cb9` | Place app PCH before project includes | Identical stable patch ID. |
| `3c453ea6` | `ec977855` | Expose Ninja explain for scpp builds | Identical stable patch ID. |
| `00900519` | `93a4f84f` | Add byte string helpers for scanner hot paths | Adapted integer wrapper spelling from int_t to int_t<>. |
| `f602c865` | `d406ff6e` | Add runtime tokenizers for PHS and JSS | Same changed lines; newer runtime umbrella includes change patch context. |
| `2d45470a` | `1b009b3f` | Fix fixed-width integer initializer lowering | Initializer cast already provided by the newer base; retained fixed-width type predicate. |
| `8a27325c` | `bf19bc72` | Add explicit string unit APIs | Same changed lines; changelog insertion follows newer releases. |
| `2914af91` | `475b61ec` | Recognize runtime static helper calls in STAN | Identical stable patch ID. |
| `eafc5e4d` | `97c8304e` | Use native buffers inside runtime tokenizer | Identical stable patch ID. |
| `a3db858f` | `d12eee45` | Expose typed runtime token buffer | Same changed lines; newer declaration-kind context. |
| `b859a1aa` | `d82b19b3` | Regenerate tokenizer runtime STAN symbols | Same changed lines; generated UI/WebView symbols change context. |
| `70c37064` | `a836f38c` | Return token buffers from runtime tokenizers | Identical stable patch ID. |
| `7a9f95ec` | `5b6adfbc` | Plan compiler support runtime features | Identical stable patch ID. |
| `e7e52518` | `7a391f1a` | Audit compiler support runtime gaps | Identical stable patch ID. |
| `292e39ad` | `34feba9a` | Document compiler support runtime placement | Identical stable patch ID. |
| `93dd3646` | `53073d59` | Support fixed-width hash keys | Identical stable patch ID. |
| `73deba69` | `ba970beb` | Cover typed hash compatibility | Identical stable patch ID. |
| `2ee6fa44` | `033d50a4` | Close typed hash priority slice | Identical stable patch ID. |
| `9c44ef65` | `c6d1e594` | Support fixed-width enum backing | Fixed-width storage mapping already in newer base; retains enum validation and adds cross-file declaration-kind recognition. |
| `b6a1da7e` | `17b9331b` | Reject raw integer enum assignment | Identical stable patch ID. |
| `609eea76` | `b4d2b500` | Add explicit enum conversion helpers | Same changed lines; newer enum/layout-probe context. |
| `32e12fd2` | `8612dc62` | Support enum hash keys | Identical stable patch ID. |
| `a7ae89d5` | `6a37f605` | Add enum name helper | Identical stable patch ID. |
| `5e99d582` | `e43f0e6b` | Detail typed hash follow-up checklist | Identical stable patch ID. |
| `367954b5` | `4d2431f4` | Add vector capacity helpers | Same changed lines; generated UI/WebView symbols change context. |
| `cead340e` | `a59f1cbd` | Document vector capacity semantics | Identical stable patch ID. |
| `6d423139` | `37da74ec` | Add source buffer runtime module | Same changed lines; newer declaration-kind context. |
| `e558c5f2` | `90bcb74a` | Check enum discipline in STAN | Same changed lines; additional compact-layout tests change insertion context. |
| `b4a38a7a` | `042c1dbd` | Add hash key benchmark probe | Identical stable patch ID. |
| `37fc48b8` | `94b77af2` | Diagnose unsupported hash keys in STAN | Identical stable patch ID. |
| `ddd7e3c4` | `698fadef` | Add high resolution monotonic timers | Identical stable patch ID. |
| `1ab57945` | `cc54dd9a` | Define typed tokenizer buffer contract | Identical stable patch ID. |
| `a856e967` | `a7937372` | Add tokenizer buffer benchmark probe | Identical stable patch ID. |
| `8ac87bff` | `212ad294` | Add source line index runtime helpers | Indentation adaptation around the same source mapping type additions. |
| `6da709de` | `92f373e0` | Record concrete compiler row arena decision | Identical stable patch ID. |
| `aad0a38d` | `30ace522` | Add runtime row arena template | Identical stable patch ID. |
| `34ccfb5d` | `287be7f5` | Add string parts builder runtime helper | Indentation adaptation around the same builder type addition. |
| `589e6889` | `62772977` | Add runtime bitset helper | Identical stable patch ID. |
| `6c00f198` | `7ca6222c` | Add runtime work queue helper | Identical stable patch ID. |
| `3db8045d` | `bdfd89ea` | Add binary codec runtime helpers | Same changed lines; newer runtime umbrella includes change context. |
| `5972e1d1` | `f3479354` | Add memory accounting helpers | Identical stable patch ID. |
| `cd160509` | `369649f9` | Add stable hash helpers | Identical stable patch ID. |
| `1a1f2342` | `2deae046` | Document compiler support runtime contract | Identical stable patch ID. |

## All 33 changed contribution files

The other 74 contribution files are byte-identical at the compared tips.

| File | Assessment |
| --- | --- |
| `CHANGELOG.md` | Later release sections; original string-unit entry retained. |
| `bin/project_services.php` | Metadata export, Ninja explain, PCH ordering and enum/hash diagnostic classification retained; expanded build integration and metadata model. |
| `docs/tokenizer_builtins.md` | Trailing blank line removed only. |
| `generators/php/specs/php_runtime_symbols_legacy.json` | Additive registrations; every old item retained unchanged. |
| `generators/php/specs/php_runtime_symbols_strict.json` | Additive registrations; every old item retained unchanged. |
| `generators/php/src/Analysis/FrontEndSymbolExtractor.php` | Expanded declaration-kind and project dependency summaries. |
| `generators/php/src/Analysis/RuntimeShallowSourceGenerator.php` | Additional type/helper stubs and newer JSON wrapper signatures. |
| `generators/php/src/Generator/Generator.php` | Newer compact-layout and integer bridge owners subsume old cases; enum helpers and validation retained. |
| `generators/php/src/Lowering/TypeMapper.php` | Declaration-kind awareness and later runtime type additions. |
| `generators/php/src/Stan/StanExpressionTypeResolver.php` | Retains enum/hash checks; expands expression reuse, build-gate selection and type support. |
| `generators/php/src/Stan/StanSemanticPass.php` | Moves expression work into shared analysis/cache path; retains enum/hash diagnostics. |
| `runtime/generated/stan/runtime_symbols_legacy.php` | New helper stubs, concrete vector signatures and JSON result wrappers. |
| `runtime/generated/stan/runtime_symbols_strict.phs` | New helper/type stubs, concrete vector signatures and JSON result wrappers. |
| `runtime/include/lang/php/support/php_value.hpp` | Header resolution changes; additive vector resize/fill/clear helpers. Original capacity helpers retained. |
| `runtime/include/modules/source/source.hpp` | Move-based source take; adds empty buffer and source-text move append. |
| `runtime/include/modules/strings/strings.hpp` | Adds contiguous text_builder; original string_parts_builder and string-unit helpers retained. |
| `runtime/include/modules/tokenizer/tokenizer.hpp` | Intentional storage/accessor and lexical evolution; see compatibility discussion. |
| `runtime/include/scpp/runtime.hpp` | Adds UI/WebView umbrella includes. |
| `runtime/include/scpp/string_t.hpp` | Adds release_native for ownership transfer. |
| `runtime/include/scpp/tokenizer.hpp` | Trailing blank line removed only. |
| `runtime/include/scpp/vector_t.hpp` | Adds resize with fill; existing capacity operations retained. |
| `runtime/specs/catalog.md` | Updates JSON return contracts to result wrappers. |
| `specs/builtins/tokenizer/token_buffer.md` | Documents compact hot token fields, side data, flags, and derived locations. |
| `specs/compiler_support_runtime_contract.md` | Adds source mapping and builder contracts; original content retained. |
| `specs/planning/compiler_support_runtime_backlog_2026_06_27.md` | Records contiguous text_builder follow-up. |
| `tests/php/types/vector/level_01/vector_008_capacity_helpers.phs` | Adds resize/fill assertions and uses clear_keep_capacity alias. |
| `tests/php/types/vector/level_01/vector_008_capacity_helpers.test-info.json` | Updates expected output for expanded vector test. |
| `tests/runtime/containers/level_01/runtime_containers_012_vector_capacity.cpp` | Adds resize/fill assertions; existing clear/capacity assertions retained. |
| `tests/runtime/native/CMakeLists.txt` | Adds text_builder target; all old targets retained. |
| `tests/runtime/source/level_01/runtime_source_001_buffer_span.cpp` | Adds empty-buffer assertions. |
| `tests/tools/run_tests.php` | Adds opt-in compile-unit-only mode; original tests continue through compile/run. |
| `tests/tools/test_scpp_stan_strict_discipline.php` | Additional discipline cases; JSON examples migrated; old enum/hash checks retained. |
| `tools/runtime_benchmarks/tokenizer_buffer_probe.cpp` | Memory estimate follows new token column widths and extended-length side data. |

## Curated test inventory

| Test | Profile/suite | Result |
| --- | --- | --- |
| `enum_001_fixed_width_backing` | legacy | PASS |
| `enum_002_duplicate_value_negative` | legacy | PASS |
| `enum_003_raw_int_assignment_negative` | legacy | PASS |
| `enum_004_conversion_helpers` | legacy | PASS |
| `enum_005_from_value_class_marker_negative` | legacy | PASS |
| `enum_006_hash_key` | legacy | PASS |
| `enum_007_name_helper` | legacy | PASS |
| `hash_005_fixed_width_key_domains` | legacy | PASS |
| `hash_006_default_and_int_key_compatibility` | legacy | PASS |
| `hash_007_unsupported_key_negative` | legacy | PASS |
| `runtime_containers_012_vector_capacity` | runtime | PASS |
| `runtime_hash_t_005_fixed_width_key_domains` | runtime | PASS |
| `runtime_source_001_buffer_span` | runtime | PASS |
| `strict_datetime_003_monotonic_precision` | strict | PASS |
| `strict_source_001_buffer_span` | strict | PASS |
| `strict_source_002_line_index` | strict | PASS |
| `strict_source_003_stable_hash` | strict | PASS |
| `strict_strings_007_explicit_string_units` | strict | PASS |
| `strict_strings_008_string_parts_builder` | strict | PASS |
| `strict_tokenizer_001_typed_buffer_accessors` | strict | PASS |
| `vector_008_capacity_helpers` | legacy | PASS |
