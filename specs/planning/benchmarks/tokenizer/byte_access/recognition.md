# Clang operator recognition and token storage

Doc Status: supporting

dev002 CPU 7, Clang 18.1.3, -O2, no LTO. Each flavor scanned its 8,192-byte operator fixture (2,560 tokens). Each of 24 balanced rounds ran all six configurations on one logical CPU, with nine batches of 128 scans per run. The cpp/php/js source-transfer and handwritten scanners use their own recognition and storage. The hybrid substitutes the handwritten recognition loop and std::string_view in the generated scanner while retaining scpp source ownership, previous-count reserve, in-slot token construction and final move. The digest versions replace vector reserve and per-token writes with the same order-sensitive unsigned checksum of offset, length and kind. Source setup, scan, and result destruction are timed.

## Median ns/token (lower is faster)

| Flavor | Generated loop + digest | Hand loop in scpp + digest | Native + digest | Generated loop + scpp vector | Hand loop + scpp vector | Hand loop + std vector | Paired loop saving with scpp vector | Share of elapsed gap closed¹ |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| cpp | 2.350 | 2.047 | 2.225 | 2.648 | 2.250 | 2.041 | 0.380 | 64% |
| php | 2.341 | 2.111 | 2.191 | 2.639 | 2.226 | 1.991 | 0.428 | 65% |
| js | 2.351 | 2.042 | 2.219 | 2.640 | 2.263 | 2.067 | 0.389 | 66% |

¹ Median paired loop saving divided by median paired generated-to-native elapsed difference. This describes substituting the whole recognizer, including cursor type, byte-view access and optimized code layout. Digest mixing has a dependency chain of its own, so vector time minus digest time is not a pure append cost.

## Storage-only replay

In a separate 24-pair alternating run on the same CPU, predecoded oracle triples are read from memory. Both paths reserve 2,561 slots, append 2,560 records, return the vector and destroy it. No source copy, classification or lookahead occurs. Construction and destruction are inside timing, file parsing and full-field verification are outside. The producer is in a separate translation unit.

| Flavor | scpp in-slot ns/token | std vector ns/token | Paired scpp minus std ns/token |
|---|---:|---:|---:|
| cpp | 1.803 | 1.955 | -0.141 |
| php | 1.786 | 1.943 | -0.157 |
| js | 1.797 | 1.970 | -0.162 |

Replay adds sequential reads of predecoded records and has a simpler loop body. Its delta cannot be added to the recognition-loop saving to partition the entire production time.

## Native inspection and verification

Both optimized cpp-flavor loops compare the next bytes directly with 0x3d, guard lookahead at the end, and select the longest operator without a helper call. The generated outer loop also tests the signed cursor for negativity before loading the current byte. The handwritten loop uses a size_t cursor. Classification and basic-block layout also differ. These runs do not isolate the cost of that sign check individually.

All nine vector builds passed the 11 PHP-oracle fixtures and the edit, failure and recovery checks. All nine digest builds matched independently computed oracle digests or the expected lexical diagnostic on the same fixtures. All six replay binaries checked every stored field against the operator oracle. The measurements cover warm repeated input on one compiler build.

From the benchmark directory, run `recognition.py FIXTURES SLOT_WORK RUNTIME WORK --cpu 7` and `replay_storage.py FIXTURES SLOT_WORK RUNTIME WORK --cpu 7` with Python 3. [Scanner samples](recognition.dev002.json) · [Storage replay samples](replay_storage.dev002.json).
