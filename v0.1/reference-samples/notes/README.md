# Compiler input samples
Doc Status: supporting

The large compiler sample is generated from the current authored PHP in
`compiler/my-try`, using the stage selection and entry template from its bootstrap
probe. Do not hand-edit generated PHS. Refresh explicitly from the repository root:

```sh
python3 compiler/my-try-cpp/tools/refresh_compiler_sample.py
```

The tool invokes the existing converter with `--target=v0.2`, assembles its native
framework helpers, and writes `my-try/phpp/prism.json`. Source hashes and repository
revision are recorded in `my-try/provenance.json`; hashes capture the actual input,
including uncommitted source changes. Logs remain in `my-try/logs/`. Refreshing
changes this benchmark input deliberately; normal builds never regenerate it.

The entry retains the old bootstrap protocol: when executing a compiled sample
from its PHS project root, `request.txt` supplies the input directory and `output.txt`
supplies the publication directory. Runtime catalogs use a repository-relative path.
Those request files are unnecessary for discovery/read benchmarks.

See [ready-worker read breakdown](read-breakdown-dev002-16.md), [dev002 with 16 jobs](concurrency-dev002-16.md), [achieved CPU concurrency](concurrency.md) and [stage measurements](metrics.md) for the local baseline and reproduction commands.

The [compact tokenizer results](tokenization-dev002-16.md) include corpus validation,
CPU/concurrency, memory, allocations and separate ready-worker timings on dev002.

## Available inputs

Paths below are relative to the repository root. Counts exclude build output.

| Sample | Source root | PHS files | Bytes |
| --- | --- | ---: | ---: |
| Small accounting | `compiler/my-try-cpp/samples/accounting-small` | 15 | 118,906 |
| Large accounting | `compiler/my-try-cpp/samples/accounting-large` | 93 | 528,088 |
| Fresh my-try conversion | `compiler/my-try-cpp/samples/my-try/phpp` | 257 | 1,943,499 |

Accounting inputs are local copies; their PHS hashes match the original projects.
The adjacent accounting provenance files record original locations and hashes. The fresh compiler conversion
includes framework support; its largest file is 101,355 bytes. Conversion and
framework assembly passed on 2026-10-26. The C++ rewrite discovered and read all
257 files / 1,943,499 bytes successfully with eight active scan/read workers.
This verifies conversion and source loading, not semantic compilation or native
execution of the converted compiler.

```sh
compiler/my-try-cpp/build/compiler/my-try-cpp \
  --catalogs runtime/metadata/modules --module core \
  --source compiler/my-try-cpp/samples/my-try/phpp --jobs 8 --timings
```
