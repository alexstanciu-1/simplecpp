# Memory optimization debt
Doc Status: planning

Tracks measured opportunities for future work, not changes to current contracts.

## MEM-001: Unused token-array capacity

Status: deferred. Recorded 2026-10-27.

Each token is exactly 8 bytes: 4-byte start offset, 3-byte length and 1-byte kind.
Token arrays grow geometrically and retain spare slots after tokenization. Source
bytes are retained separately, unchanged; tokens reference them without copying
their spelling.

Measured across the three retained samples:

| Sample | Source bytes | Used token bytes | Allocated token capacity |
| --- | ---: | ---: | ---: |
| Accounting small | 118,906 | 155,752 | 221,184 |
| Accounting large | 528,088 | 695,536 | 1,178,080 |
| my-try conversion | 1,943,499 | 2,388,712 | 3,605,256 |
| **Total** | **2,590,493** | **3,240,000** | **5,004,520** |

For these 365 files / 405,000 tokens:

- Used token bytes / source bytes: **1.251×** (ratio of totals).
- Allocated token capacity / source bytes: **1.932×**.
- Unused capacity: **1,764,520 bytes**, about **35.3%** of token capacity.
- Source plus allocated token capacity: **2.932×** source size.
- Source plus exactly sized token arrays would be **2.251×** source size.

These are payload/capacity measurements, not process RSS. They exclude ownership
wrappers, allocator overhead and transient coexistence of old/new generations.
The unweighted mean of individual file token/source ratios is **1.227×**.

### Options to evaluate later

- Compact completed token arrays before publication, possibly only when spare
  capacity exceeds a useful threshold.
- Improve initial capacity estimates or growth policy using measured token density
  and prior-generation counts.
- Compare against a counting pass only if its extra scan is justified by savings.

Do not assume shrinking is free or guarantees exact capacity. Compaction may add
an allocation and copy/move of token records, temporarily increase peak memory,
and increase tokenization latency. Preserve immutable source/token ownership and
failure-safe publication; do not change the 8-byte token layout for this debt.

### Acceptance evidence

Compare retained capacity, peak memory, allocation count/bytes, tokenization time
and achieved concurrency on all three samples. Cover both first tokenization and
changed-file updates, including growth and shrinkage. Validate identical token
spans/kinds and retained-generation lifetimes. Keep normal timings separate from
allocation instrumentation. Record the tradeoff before choosing a policy.

Reproduce current capacity measurements with the `tokenization-profile` target,
passing each sample root, a job count of `1` or `16`, and mode `normal`.
