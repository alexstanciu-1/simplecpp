# JSS Validation And Diagnostics

Use this guide for JSS build/run/debug work.

## Standard Commands

Focused transpilation:

```bash
scpp main.jss
```

Project build/run:

```bash
scpp build
scpp run
```

Diagnostics:

```bash
scpp error
scpp full-error
scpp last-run
scpp full-last-run
```

Docs:

```bash
scpp docs jss
scpp docs strict
scpp docs diagnostics
```

## End-To-End Smoke Project

Use this to prove an installed checkout can create, build, and run a JSS project.

Create `prism.json`:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "strict"
      }
    }
  },
  "entrypoint": "main.jss"
}
```

Create `main.jss`:

```js
class Box {
	name: string = "ready";
}

let box: Box = new Box();
let value: int = 4;
let alias = &value;
alias = 9;
let addOne = (x: int): int => x + 1;
let scores: hash<int> = {"a": 1, "b": 2};
delete scores["a"];

print(box.name, ":", value + 1, ":", addOne(value), ":", scores["b"], "\n");
```

Run:

```bash
scpp build
scpp run
```

If a fresh checkout reports a missing reusable runtime artifact:

```bash
scpp build --build-runtime
scpp run --build-runtime
```

Expected stdout:

```text
ready:10:10:2
```

Inspect:

```bash
cat .prism/jss/main.phs
```

Useful expected lowering fragments:

```phs
$alias =& $value;
$addOne = fn($x int): int => $x + 1;
unset($scores["a"]);
```

This smoke project proves class construction, property access, references, typed arrows, hash mutation, delete lowering, STAN-classified JSS emission, PHS generation, native build, and binary run.

In v1 alpha, STAN may still print advisory JSS warnings. Treat `Static Analysis: 0 errors` plus successful build/run and expected stdout as the pass condition.

## Repo Tests

For frontend lowering:

```bash
php tests/tools/test_scpp_jss_samples.php
```

For first-slice parser/emitter behavior:

```bash
php tests/tools/test_scpp_jss_frontend_first_slice.php
```

For real `.jss -> PHS -> build/run` coverage:

```bash
php tests/tools/test_scpp_jss_project_build_run.php
```

## Reading Failures

JSS failures can happen in layers:

- JSS tokenizer/parser
- JSS semantic validator
- JSS summary extraction
- STAN frontend classification
- PHS generation
- native C++ compile
- runtime

Keep the layer boundary clear.

If emitted PHS is wrong, fix JSS lowering.

If emitted PHS is right but PHS build/run fails, fix or document the PHS/STAN/runtime layer. Do not create a parallel JSS semantic path.

## Useful Artifacts

- `.prism/jss/*.phs`: classified PHS intermediate for JSS project builds
- `.prism/generated/*.cpp`: generated native source for inspection
- `.prism/generated/*.line.tsv`: source mapping evidence
- `.prism/last_error.json`: saved build/runtime diagnostic
- `.prism/last_run.json`: saved run diagnostic

Patch authored source or owning generator/runtime code, not generated artifacts.

## Known Alpha Diagnostic Notes

Optional chaining:

- JSS can lower `object?.member` to PHS `?->`
- PHS nullsafe result typing is incomplete for scalar/non-nullable member values
- GitHub issue #207 tracks proper downstream implementation

Wrapper results:

- use `take(...)`
- keep success/failure/error outputs explicit
- do not rely on truthiness

Dynamic values:

- stabilize decoded/dynamic values at typed boundaries
- inspect runtime type failures with `scpp error` first

## Build Cost and Analysis Triage

For unexpectedly long builds or excessive rebuilds, start with `scpp explain-build`, then `rebuild-fanout` and `project-units`. Use the focused `generated-files`, `ninja-explain`, `action-identity`, `object-cache`, `build-planner`, or `modules` views as needed. Report which outputs changed and why; follow `docs/ai_onboarding/workflows.md` before changing cache/grouping settings.

STAN separates confirmed build-blocking source errors from model-incomplete advisory findings. Inspect `scpp stan` for the full report and retain supported discipline checks. A successful native build alone does not prove the analyzer models every receiver or member.

## Reusable Runtime Maintenance

Run `scpp runtime-build` from the consuming project to prepare its configured
reusable runtime. For shared-eligible configurations this includes the base and
requested optional module artifacts, even when the base already exists. Use
`scpp runtime-build --force` after a runtime ABI change; add `--release` when
maintaining release artifacts. Then use the normal `scpp build` / `scpp run` flow.
Custom configurations retain project-local composition.

For typed-argument compile failures, read the error and its notes together: GCC
may explain the mismatch at the call, while Clang supplies the typed explanation
at the parameter declaration. Debug builds preserve full debug information for
source-mapped runtime errors with precompiled headers.
