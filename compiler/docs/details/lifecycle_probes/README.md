# Lifecycle contract probes
Doc Status: supporting

These are focused investigation witnesses, not additions to the compiler regression
suite. See [findings and authority](../lifecycle_contracts.md). They do not implement
new prototype features. Native tracing types intentionally exceed PHS struct member
eligibility so native order/failure behavior can be observed independently.

Verified on 2026-09-19 with the commit in `tools/toolchain.json` and Clang 18.

## Reproduce the successful source and native witnesses

Run the following Python snippet from the repository root. It uses the configured
S2S executable, creates an isolated strict project, and keeps generated files outside
the repository. Runtime preparation may make the first source build appreciably
slower. Subsequent builds in that scratch project reuse the runtime artifact.

```python
import json
import shutil
import subprocess
import tempfile
from pathlib import Path

repo = Path.cwd()
config = json.loads((repo / "tools/toolchain.json").read_text())
scpp = (repo / config["scpp"]).resolve()
upstream = scpp.parent.parent
probes = repo / "docs/details/lifecycle_probes"
scratch = Path(tempfile.mkdtemp(prefix="scpp-lifecycle-contracts-"))
print(scratch)

subprocess.run(["php", str(scpp), "init", "--php-profile=strict"], cwd=scratch, check=True)
project = json.loads((scratch / "prism.json").read_text())
project["build"]["cxx"] = "clang++-18"
project["runtime"]["modules"] = []
(scratch / "prism.json").write_text(json.dumps(project, indent=4) + "\n")
shutil.copyfile(probes / "source.phs", scratch / "main.phs")
subprocess.run(["php", str(scpp), "build", "--build-runtime"], cwd=scratch, check=True)
result = subprocess.run([str(scratch / ".prism/build/main")], check=True, capture_output=True, text=True)
assert result.stdout == "0:0\n2:1:1:12:11\n3:7:3:4:3\n9:5\n", result.stdout
print(result.stdout, end="")

native = scratch / "runtime_probe"
subprocess.run(["clang++-18", "-std=c++20", "-O0", "-I", str(upstream / "runtime/include"),
                str(probes / "runtime.cpp"), "-o", str(native)], check=True)
subprocess.run([str(native)], check=True)
```

Observed source stdout:

```text
0:0
2:1:1:12:11
3:7:3:4:3
9:5
```

Observed native stdout:

```text
composition, partial construction, shared/weak and unique: OK
```

Source build used the public `scpp build` path with STAN enabled; no `--no-stan`
bypass was used. Build returned success and the executable matched the output,
but the later completed STAN report recorded **one error-bucket diagnostic and
ten warnings**. It reported an unknown receiver for `$b->child->value` after
typed copy initialization, and maybe-uninitialized uses of default-constructed
struct locals. This is evidence of transpilation, native build and execution,
**not a clean STAN result**. Inspect `.prism/cache/stan_report.json`; an upstream
build that waits for these diagnostics may reject the probe. Do not bypass STAN
to claim acceptance. Native assertions are active (`-O0`, no `NDEBUG`). No threading,
production exception support, LTO integration, or compiler performance is proved.

The source witness uses current vector indexed access. Its successful execution
is evidence of implementation behavior; the reference-safety conflict remains
explicitly unresolved in the findings.

## Additional transpilation-only observations

Write each snippet to a separate `.phs` file **outside the positive project** and
run `php <configured-scpp> <file.phs>`, capturing stdout and stderr. The public
single-file command emits C++ source; it does not compile/link that C++.

| Input | Observed result |
|---|---|
| `struct row { public string $text; }` followed by `$value row;` | Exit 3: `Struct field row::$text uses unsupported first-slice field type string at line 1.` |
| `struct row { public int32 $value; }` followed by `$value row = new row();` | Exit 0, emits `row value = create<row>();`. Runtime `create<T>` returns `shared_p<T>`; the generated shape conflicts with the agreed by-value struct rule. |
| `function measure(string $text): int { return strlen($text); }` | Emits `int_t<> measure(const string_t& text)`. |
| `function edit(string $text): string { $text .= "!"; return $text; }` | Emits `string_t edit(string_t text)`. |

These observations do not prove successful execution of the snippets or every
parameter-aliasing case. Preserve the distinction between normative contracts,
generated shape, and executed behavior when reusing this evidence.
