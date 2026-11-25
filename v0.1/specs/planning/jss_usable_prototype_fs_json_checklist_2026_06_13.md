# JSS Usable Prototype FS JSON Checklist
Doc Status: planning

Date: 2026-06-13

Purpose: define one narrow, real, usable JSS prototype lane centered on filesystem + JSON + `take(...)`, and track the minimum downstream truths required for that lane to count as genuinely usable.

This is a planning artifact, not semantic authority.

## Scope

The target flow is:

1. write JSON text to a file through `fs.put(...)`
2. read JSON text from a file through `fs.get(...)`
3. extract wrapper results through `take(...)`
4. decode JSON through `json.decode(...)`
5. read decoded fields from the resulting `dynamic` carrier
6. build and run through the normal:

```text
.jss -> JSS frontend -> PHS -> normal build/runtime path
```

This lane should use real project configuration and real runtime modules, not only sample transpilation.

## Prototype Target

The first usable JSS prototype should be able to support a small project equivalent in spirit to:

```js
let file: string = "sample_strict_fs_json.txt";
let err: error;
let written: int = 0;

if (!take(written, err, fs.put(file, "{\"name\":\"alex\",\"count\":2}\n"))) {
    print("write_error\n");
    return;
}

let data: string = "";
if (!take(data, err, fs.get(file))) {
    print("read_error\n");
    return;
}

let decoded: dynamic = json.decode(data);
print(written, "\n");
print(strlen(data), "\n");
print(decoded["name"], "\n");
print(decoded["count"], "\n");

if (!fs.remove(file)) {
    print("remove_error\n");
}
```

Expected output:

```text
26
26
alex
2
```

## Checklist

| Capability | Owner | Status | Proof | Remaining blocker |
| --- | --- | --- | --- | --- |
| JSS helper-family parsing for `fs.*` and `json.*` | JSS frontend | done | existing helper-family samples and project transpilation | none for this lane |
| JSS `take(...)` surface for `result<T>` | JSS frontend + STAN contract bridge | done for this lane | existing `fs.get(...)` and `io.*` JSS samples plus project tests | broader wrapper-family cleanup remains outside this lane |
| PHS/runtime `fs_put(...)` result contract | PHS/runtime | done | existing strict PHS sample and runtime surface | none for this lane |
| PHS/runtime `fs_get(...)` result contract | PHS/runtime | done | existing strict PHS sample and runtime surface | none for this lane |
| PHS/runtime `json_decode(...)` return to `dynamic` | PHS/runtime | done | existing strict JSON sample plus JSS/PHS project validation | none for this lane |
| Decoded JSON field access from `dynamic` | PHS/runtime | done for this lane | strict JSON runtime sample and project run behavior | deeper typed normalization from dynamic remains separate |
| Normal `.jss -> PHS -> build` path | build/project path | done | JSS project build/run tests | none for this lane |
| Real runtime module activation for `json` and `filesystem` | project/build/runtime + STAN preflight | done for this lane | project `prism.json` with modules, successful build/run, and inactive helper-module preflight failure coverage | broader module/profile matrix remains outside this lane |
| Real project-level validation, not only transpilation | tests/project path | done in this lane | dedicated JSS fs/json project build/run test | broader project matrix still open |

## Explicit Non-Goals For This Lane

These should not block the fs/json usable prototype lane:

- `undefined`
- optional chaining
- broad JavaScript truthiness/coercion
- closures / arrow functions
- object-bag semantics beyond `mixed` JSON access
- delete/unset policy
- typed normalization from `dynamic` into typed locals or typed containers

## Practical Success Rule

This lane counts as usable when all of the following are true:

1. a `.jss` project with `filesystem` and `json` modules builds successfully
2. the emitted `.phs` shows the expected helper-family lowering
3. the built binary runs successfully
4. wrapper extraction through `take(...)` works on real `fs.put(...)` / `fs.get(...)` results
5. `json.decode(...)` output can be used immediately through the currently honest `dynamic` access path

## Error-Path Lane

The same usable prototype lane should also prove:

1. missing-file `fs.get(...)` failure is handled explicitly through `take(...)`
2. malformed JSON surfaces a real runtime parse diagnostic
3. both behaviors are validated through the normal `.jss -> PHS -> build/run` path
4. wrapper success/failure preserves the previous output value on failed extraction for the current strict helper contracts

## Follow-Up Boundary

Once this lane is stable, the next useful step should be:

- broader runtime-heavy sample coverage that stays inside the already-proven helper/module/take/dynamic contracts; current proof also covers the strict string/IO sample shape with `fs.mkdir`, `io.open`, nested `implode(explode(...))`, `io.write`, `io.read`, `strtoupper`, and cleanup helpers

Not:

- widening the language surface into unrelated JS-like features
