# Parked LLVM experiment
Doc Status: supporting

Retained for existing regressions only. New language semantics belong to
[shared preparation](../../04_analyze/prepare/README.md) and specialization-attached
facts. Before LLVM development resumes, review/adapt this backend to consume those
facts, leaving LLVM-specific lowering here. The two paths are not consolidated yet.

## Owners and flow

| File | Retained responsibility |
| --- | --- |
| `legacy_names.php` | `LLVM_Legacy_Name_Preparation`, token-indexed lookup |
| `legacy_templates.php` | Symbolic template checks, including unused templates |
| `prepare.php` | Concrete instances, pending registry and local/parameter storage |
| `structs.php` | Concrete struct types/fields |
| `structures.php` | Policy, legacy preparation maps and LLVM output records |
| `generate.php`, `functions.php`, `expressions.php`, `statements.php` | Function/block lowering and shared place/value operations |
| `write.php` | Serialize module records |

`Compiler::llvm()` invokes program preparation, which resolves names, checks templates
and queues ordinary/explicit template instances. Generation creates independent
function/block/operand records and serializes modules. Ordered records use Storage;
named types/fields/instances use Keyed_Storage. Sparse declaration indexes and legacy
name maps remain separate experimental data, not shared preparation owners.

## Regression boundary

The experiment's policy maps `int` to LLVM `i32`; that is not the Simple C++ default
integer contract. Existing cases cover explicit integer locals, calls and reference
parameters, fixed arrays, integer-field structs and explicit template arguments.
Unsupported cases reject under the experiment policy. Do not infer general language
support or new restrictions from this backend.

Module records live in `Model::$llvm_files`. Source names are encoded for LLVM;
instance/declaration identity disambiguates targets. Output files and native execution
belong to the coordinator/`06_native` host path. The backend's token-indexed maps
remain debt until shared-fact convergence.

From the repository root, with an existing empty output directory:

```bash
php compiler/my-try/tests/llvm.php /tmp/llvm-test-output
```

This runs PHP assertions and writes IR plus expected execution data. It does not
compile the compiler itself. The broader test runner can compile/run those programs
when requested. Historical implementation detail is retained in
[the backend checkpoint](../../docs/archive/llvm_backend_checkpoint.md).

## Parking boundary — 2026-10-25

The user authorized removing this prototype from the C++ bootstrap scope. Files
moved from `05_backend/llvm/` here; algorithms are preserved. `LLVM_Prototype`
owns host-only output/reset/emission. Compiler's existing LLVM entrypoints are
host-only wrappers marked `@scpp-no-export`. Shared Model/lifecycle no longer own
prototype outputs. Host tests inspect `LLVM_Prototype::$files`.

`boot.php` still loads this prototype for explicitly selected host regressions.
The bootstrap snapshot copies active stages and excludes `prototypes/`; its small
`bootstrap_driver.php.in` compiles PHS directories to C++ artifacts, without old
LLVM proof branches. Native LLVM validation is parked; `native_validate.py` requires
`--types-only`. This is not completion of native compiler bootstrap validation.

Shared C++ lifecycle resets no longer alter prototype output. Explicit prototype
entrypoints/reset own that state, clearing it before frontend work to avoid publishing
stale LLVM output when a prototype compilation fails.
