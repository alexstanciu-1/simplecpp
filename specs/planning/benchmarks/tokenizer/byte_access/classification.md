# Byte classification and equals lookahead

Doc Status: supporting

The control is the source-transfer scanner with a proven nonnegative cursor, checked split view, in-slot token append, previous-count reserve and last-use move. Its scanner object matched the prior build byte-for-byte for each flavor. The table variant uses a constexpr 256-byte ASCII classification table for whitespace, identifier start/continuation and equals dispatch. The lookahead variant uses remaining length to select a two-byte case, compares both bytes through the checked view even if the first mismatches, and handles a one-byte tail separately. Keyword recognition and output storage are identical. The combined variant applies both changes.

## Pinned throughput

dev002 CPU 7, Clang 18.1.3, -O2, no LTO. For each corpus and flavor, 24 rounds cover every ordering of the four binaries once. Each timed run includes nine batches of 128 complete scans with result destruction. Ratios are medians of within-round throughput ratios. Values above 1 are faster. The operator column for control gives absolute throughput; ratios in the other columns use their own row’s control.

| Corpus | Flavor | Control MiB/s¹ | Table / control | Lookahead / control | Both / control | Lookahead effect with table (both / table) |
|---|---|---:|---:|---:|---:|---:|
| operators | cpp | 1263.3 | 1.022× | 0.764× | 0.752× | 0.736× |
| operators | php | 1268.5 | 0.980× | 0.765× | 0.748× | 0.766× |
| operators | js | 1277.4 | 1.014× | 0.769× | 0.746× | 0.739× |
| mixed | cpp | — | 1.166× | 0.944× | 1.157× | 0.991× |
| mixed | php | — | 1.158× | 0.938× | 1.079× | 0.930× |
| mixed | js | — | 1.191× | 0.929× | 1.125× | 0.947× |
| identifiers | cpp | — | 1.362× | 0.971× | 1.355× | 0.989× |
| identifiers | php | — | 1.352× | 0.975× | 1.330× | 0.985× |
| identifiers | js | — | 1.360× | 0.964× | 1.333× | 0.986× |

¹ Operator input is 8,192 bytes and emits 2,560 tokens. Secondary corpora help reveal workload-specific effects. No hardware counters were collected.

The table alone provides the highest mixed and identifier throughput in each flavor. On operators its small effect changes sign across flavors. Both lookahead comparisons favor the original short-circuit path on the operator corpus.

## Verification and native inspection

All 12 binaries passed the 11 PHP oracle fixtures and the edit/failure/recovery sequence. Each also matched explicit token expectations for runs of one through nine equals signs ending at EOF or followed by a space or an identifier. These cases check longest match, exact offsets and preservation of the following byte.

In the inspected cpp-flavor object the table lives in read-only data. Original lookahead branches after comparing the first byte, skipping the second read on mismatch. On its two-byte path the alternative emits two byte comparisons and condition-code operations; length and checked-access branches still occur. Thus the timings measure the complete alternatives, including code layout. They do not isolate individual branch misses.

Reproduce with `python3 -B classification.py FIXTURES SLOT_WORK SIGNED_WORK RUNTIME WORK --cpu 7`. [Raw samples and binary hashes](classification.dev002.json).

The [whole-file comparison](source_classification.md) tests the table on repository programs with the original short-circuit lookahead.
