# Packed codec audit evidence

Doc Status: planning

These standalone probes observe the actual v0.1 donor. They intentionally expose
known defects and are not a correctness suite or part of CTest. No production
runtime code is copied or modified. The allocation hook is copied from the v0.2
hash tests; it intercepts ordinary allocations used by these scenarios, not every
possible allocation mechanism.

From the repository root, with Clang and libc++ available:

```sh
python3 v0.2/specs/planning/evidence/packed_codec_audit/run.py
```

Use `--output /tmp/packed-audit.json` to save output. Each allocation-failure
scenario runs in a fresh process, so previous pool corruption cannot affect it.
The runner fails on compilation/process errors, but does not assert that observed
defects are desirable. After repair, different output is expected.

`observed.json` records the investigation run, including revision and compiler.
PHP-target probes test both compile-time normalization modes. The candidate union
in `probe.cpp` only measures a possible payload-slot size; it is not a candidate
codec implementation or benchmark. Allocation-failure probes inspect counts and
membership, avoiding iteration or value access through potentially malformed arrays.

See [the investigation](../../runtime_packed_storage_investigation.md) for findings,
limitations and the proposed decision.
