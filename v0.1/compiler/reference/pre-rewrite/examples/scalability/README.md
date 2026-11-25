# Scalability samples
Doc Status: supporting

Seven deterministic projects for the current [PHP prototype](../../README.md).
Directory labels use binary units: KB = 1,024 bytes, MB = 1,048,576 bytes.
Sizes count participating `.phs` bytes, excluding manifests and this README.

| Project | Source bytes | Files | Named functions |
|---|---:|---:|---:|
| [64 KiB](64kb/project.json) | 65,545 | 11 | 268 |
| [128 KiB](128kb/project.json) | 131,227 | 19 | 535 |
| [256 KiB](256kb/project.json) | 262,345 | 36 | 1,068 |
| [512 KiB](512kb/project.json) | 524,335 | 69 | 2,133 |
| [1 MiB](1mb/project.json) | 1,048,807 | 136 | 4,265 |
| [5 MiB](5mb/project.json) | 5,243,107 | 669 | 21,315 |
| [10 MiB](10mb/project.json) | 10,485,859 | 1,335 | 42,627 |

All are within 0.2% of target. Each contains a main file, shared scalar/void
helpers and groups of up to 32 functions. Main invokes every generated function;
functions call the shared helpers and exercise typed integer locals, copying,
assignment, nested scopes, shadowing and returns. No comments or unused source
pad the size. There are no parameters, operators, branches or unbounded recursion.
Every baseline executable returns exit status **42**.

Generate and benchmark from the repository root:

```sh
python3 benchmarks/scalability/generate.py
python3 benchmarks/scalability/run.py
# The default series stops at 1 MiB; request the larger samples explicitly.
python3 benchmarks/scalability/run.py --sizes 5mb 10mb
```

The generator rewrites known generated files. The benchmark verifies
[inventory fingerprints](inventory.json), uses disposable copies, and changes
the last generated function's body so the updated executable returns **43**.
Original samples remain unchanged during measurements. The final group can
contain fewer than 32 functions; its actual replacement count is recorded.

See the [benchmark method](../../benchmarks/scalability/README.md) and
[first results](../../docs/details/scalability_first_run.md).
