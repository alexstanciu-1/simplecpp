# Integer entry slice: owners and verification

Doc Status: supporting
Date: 2026-10-30

The [entry contract](../../specs/compiler_entry_v0_2.md) owns meaning and limits.
This slice implements `$ret int = 8; return $ret;` and its cpp/js equivalents through
parsing, collection, preparation, C++ emission, native compilation and exit status 8.

## Inspect each stage

From the repository root, after building the runtime with its default CMake preset
(`cmake --preset default` and `cmake --build --preset default` from the repository root):

```sh
php compiler/main.php parse php compiler/examples/entry.php.scpp
php compiler/main.php collect php compiler/examples/entry.php.scpp
php compiler/main.php prepare php compiler/examples/entry.php.scpp build/clang/native-layout/facts.json
php compiler/main.php cpp php compiler/examples/entry.php.scpp build/clang/native-layout/facts.json build/entry-slice/entry.cpp
clang++ -std=c++23 -stdlib=libc++ -I runtime/generated/public build/entry-slice/entry.cpp build/clang/libscpp_runtime.a -o build/entry-slice/entry
```

Run `build/entry-slice/entry`; its exit status is 8, not a compiler failure.
The cpp/js examples live beside the PHP example and use the corresponding flavor
argument. The compiler publishes only the requested C++ file. The existing runtime publication
and library are consumed as-is; this slice does not redesign app-specific runtime
generation or PRJ configuration. `prepare` exposes decimal constant strings, exact
binding IDs, semantic types, conversion decisions and work counts. Inspection JSON
is a debugging view, not a serialized compiler IR or stable external protocol.

## Owners

| Owner | Responsibility |
| --- | --- |
| `03_parse/parser.php`, syntax structures | Shared return grammar and source spans |
| `03_parse/collect/` | Parser-invoked local collection of declaration identities and unresolved reads |
| `04_analyze/prepare/structures.php` | Canonical scalar identity, bindings, values, statements, target range and completed entry |
| `04_analyze/prepare/prepare.php` | Source-order visibility, type/literal admission, constant facts and entry conversion |
| `06_native/target.php` | Adapt native layout-probe facts to the semantic target range |
| `05_backend/cpp/emit.php` | Typed C++ spelling and the checked exit adapter |
| `05_backend/cpp/publication.php` | Whole-file output staging and replacement |
| `compiler/frontend.php`, `host/` | Stage coordination and presentation |

The donor's separation of occurrence collection, semantic preparation and backend
facts is retained. Incremental work graphs, dependency subscriptions, scheduler
rounds, parallel preparation and global retained roots are not copied. One explicit
local scope is enough for this slice; later scopes must extend that owner rather
than embedding name resolution in the emitter.

## Agreed verification checklist

- [x] All flavors retain equivalent declaration/return structures and precise spans.
- [x] Collection retains original syntax identities without resolving references.
- [x] Preparation binds the read to the exact declaration and canonical int64 type.
- [x] Duplicates, unresolved types/names, self-initialization, forward reads and
  literal/known-exit overflow fail before emission with source locations.
- [x] The backend rejects unprepared syntax and emits from settled facts only.
- [x] All three generated executables compile, link and exit with 8.
- [x] Unknown-value exit checks admit both native-int endpoints and reject values
  immediately outside them; the adapter is exercised with runtime arguments.
- [x] A mismatched native-int target fails the generated C++ assertion.
- [x] Failures preserve old output and do not create new publication directories.
- [x] Repeated emission is deterministic; a reused preparation worker recovers
  after failure without retaining the previous source's bindings.
- [x] Statement/expression visit and name-read lookup counts scale linearly on
  declaration/reference chains. Timings separate scanning, combined parsing/collection,
  preparation, rendering and file publication; native build/run timings are separate.

Commands:

```sh
php compiler/tests/tokenizer.php
php compiler/tests/parser.php
python3 -B compiler/tests/cli.py
php compiler/tests/entry.php
python3 -B compiler/tests/entry_native.py
php compiler/tests/entry_scaling.php
```

`entry_native.py` requires the default Clang/libc++ runtime build. Its C++ runtime-input
driver proves the checked adapter, not new source syntax. `entry_scaling.php` uses
five in-process repetitions per size, reports medians and asserts actual work counts;
it sets no machine-dependent timing threshold. Bootstrap timing excludes PHP process
startup. Native tests report subprocess-inclusive source-to-C++, build and run timings.
No like-for-like donor speedup or general compiler performance claim is made.

## Measured baseline — 2026-10-30

Local PHP 8.5.7 CLI, Clang 18.1.3/libc++; five repetitions per chain size, medians
in milliseconds. Input construction is excluded from the stage columns below;
source snapshot construction is measured separately by the runner.

| Locals | Tokenize | Parse | Collect | Prepare | Render | Publish |
| ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| 100 | 15.655 | 1.85 | 0.084 | 0.366 | 0.143 | 0.399 |
| 1000 | 157.521 | 16.976 | 0.731 | 3.453 | 1.237 | 0.473 |
| 5000 | 799.614 | 88.692 | 4.017 | 18.979 | 6.52 | 1.151 |

Each size recorded N+1 statement visits, N+1 expression visits and N name-read
lookups. This supports linear work for this straight-line subset; it does not
predict future nested-scope, overload or incremental workloads. Tokenization is
currently the largest measured stage and is left for a separate reviewed slice.
The three small native builds took about 124–127 ms each; source-to-C++ subprocesses
about 43–45 ms, and execution about 2 ms. These include process overhead and are
not directly comparable with the in-process preparation figures above.
