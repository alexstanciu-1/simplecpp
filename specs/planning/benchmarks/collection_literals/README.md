# Collection construction research harness

Doc Status: planning

Isolated experiments only. These concrete prototypes do not implement source-language
collection literals or change the production runtime. Start with [the report](report.md)
or [the interactive measurements](report.html).

Eight forms: append, presized append, brace-list constructor, brace-list static
factory, array constructor, array static factory, consuming-array constructor and
consuming-array static factory. Inputs: 3/64/512 integers, 16 short strings, 16
heap-backed strings with embedded NUL, and an 8×16 nested integer vector.

Every bulk path presizes destination element capacity. `reserved_append` provides
the distinct comparison for presizing without reducing the emitted call count.

The strings use the production scpp implementation. The container is an isolated
concrete pimpl implementation common to every candidate. All native storage templates
are private to model.cpp; only the brace-list signatures expose std::initializer_list.
Consuming factories move only from private input arrays; lvalues are copied into
those arrays first. This is an experimental transport, not adopted language semantics.

Run from this directory, using a published runtime snapshot:

```sh
python3 run.py --runtime /path/to/runtime --out /tmp/construction-results --repeats 5 --profiles 3 --sites 12
python3 verify.py --runtime /path/to/runtime --out /tmp/construction-proof --cxx clang++
python3 verify.py --runtime /path/to/runtime --out /tmp/construction-proof-gcc --cxx g++
```

`--smoke --sites 1 --repeats 1 --profiles 1` runs smaller local validation. Do not treat WSL smoke
timings as dev002 results. Run the full measurement sequentially on dev002. The five
rounds randomize candidate order with a fixed seed and retain all raw samples.
Clean compile/link wall timing runs without -ftime-trace. Phase profiles use three
separate compilations per fixture. Clean runtime code has no allocator override or
counter branches, verified by a symbol check. Separate BENCH_INSTRUMENT binaries
collect counters and assert expected no-growth buffer counts and exact capacity.
Compile/link failures and incorrect digests at any site abort the run. Repeated
allocation profiles must agree. No packages beyond Python, Clang and native development tools
are required. `verify.py` adds ASan/UBSan, stack-use-after-return and leak detection, and injects failure at every allocation position
in the instrumented model only. Distinct entry IDs prove exact order/failure prefixes.
The proofs cover existing-destination preservation, repeated inner-vector/string
lvalues, mutation independence, temporary/source lifetime and reserve boundaries.

`runtime_snapshot.tar.gz` contains the source/header snapshot used on dev002.
Extract it under a temporary directory and pass its `runtime` folder to reproduce
the same string implementation. It is benchmark evidence, not runtime publication.
`results.dev002.json` records machine details, flags, source hashes, raw samples,
setup costs, compile phases, allocations/copies and emitted object/executable sizes.
`report.py` regenerates the Markdown and offline HTML views from that JSON.

Limits: shared host without exclusive CPU reservation; no LTO/PCH/cache acceleration;
no native AST/prepare/emit timings, source-flavor comparison or production adoption.
The measured 512-element maximum does not establish safe stack policy for huge literals.
Allocation counters exclude stack/static storage and allocator overhead. Runtime
counters and allocator overrides are compiled out of the timing model entirely.
Revision 1 results are retained in results.dev002.v1.json as superseded evidence.
