# Concrete specialization implementation partitions
Doc Status: supporting

Candidate: `33cee8fad` plus the accompanying backend/test changes, 2026-10-25.
The approved preparation-concurrency work is separate and remains outstanding.

## Implementation

The existing specialization artifact assembler assigns each canonical entry name to
one of 256 SHA-256-prefix buckets. Definitions remain name-ordered, with one owner;
headers, layouts, semantic demand and runtime ABI stay with their existing owners.
Only occupied buckets are published. No language or binding metadata change.
This is a fixed distribution policy, not a hard byte limit or an adaptive scheduler.

Splitting exposed three latent header cycles involving nullable class fields in
`conversion_decision`, `if_node` and `local_environment`. Runtime operation result
prototypes now request existing `stored_value` completeness and preserve forward
declarations; native implementation dependencies remain complete. No special-case
source names or bucket-dependent include ordering were introduced.

## Measurements

On dev002 (Ryzen 5950X, 32 logical CPUs), Clang 18, `-O2`, same converted compiler
input and native flags as the preceding performance audit:

| Measurement | Monolith baseline | Partition candidate |
|---|---:|---:|
| Specialization implementation units | 1 | 256 |
| Largest source unit | approximately 15.3 MB | 113,673 bytes |
| Longest unit, concurrent compilation | 155.48 s | 6.78 s |
| Specialization compilation phase | 155.48 s | 38.50 s |
| Peak RSS of one Clang process | 2,518,040 KiB (serial baseline) | 285,964 KiB |

The partition phase used 32 shared-runner workers. Its elapsed time is reconstructed
from GNU-time action intervals after the diagnostic link continuation. All 256
objects compiled successfully. Unchanged source/runtime objects and layout results
were reused from the prior baseline; this is **not** a new full clean-build timing.
Higher translation-unit count repeats header parsing; total machine memory and
aggregate CPU cost must not be inferred from the per-process peaks or summed wall
intervals. No production native-build scheduler change is included.

The replacement objects linked with the baseline's other objects. That native
compiler compiled a new program; its generated application built at O2 and printed
`42`. This verifies the partitioned compiler application, not native execution of
the new emitter implementation itself (the benchmark input is deliberately fixed).

The updated compiler source also converted and completed PHP-hosted self-bootstrap:
107.75 s wall, 805,512 KiB peak RSS, PHP peak heap 802,549,760 bytes. It published
256 implementation units. The probe's `fatal` field contains a nonfatal first-use
`lstat` warning for the absent manifest; exit status is zero, failure is null and
all stages completed. No native second-generation rebuild is claimed for this slice.

## Proofs and attempts

- `tests/runtime_type_bindings.py`: O0/O2/ASan+UBSan lifecycle runs, independent
  compilation of nullable self-initialization conversion headers, partition
  discovery-order stability, exactly-once definitions, isolated addition, warm
  inode preservation, monolith retirement and empty-bucket cleanup. Final run:
  `/tmp/specialization-partitions-bindings-04`.
- `tests/nullable_handle_representation.py`: O0/O2/ASan+UBSan ownership and
  template-free application checks, split implementations. Run `...-nullable-01`.
- `tests/empty_defaults.py`: ordinary/concrete and custom-provider execution plus
  retained rejection/recovery. Run `...-defaults-01`.
- `tools/bootstrap_probe.py`: final candidate in `/tmp/compiler-partition-bootstrap-02`.

The first full-workload partition attempt failed in three headers, motivating the
stored-value dependency correction above. The corrected attempt compiled every
unit; its diagnostic linker script initially referred to the temporary object name
rather than the published cache artifact. `dev002_finish.py` corrected that test
harness reference and linked the existing successful outputs without recompilation.
The archived benchmark recipe includes the correction for future runs.

Raw remote artifacts: `/home/alex-ai/compiler-self-o2-20261025/partition-benchmark-02`.
Commands, per-unit timings, smoke result and bootstrap resources are retained here.
PHP runner fallback to CLI on FPM unavailability/version mismatch is expected.
