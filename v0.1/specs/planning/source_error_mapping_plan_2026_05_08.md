# Source Error Mapping Plan
Doc Status: planning

Date: 2026-05-08

Purpose:
- isolate the work needed to map failures back to original `.phs` / `.php` source locations
- separate existing narrow location support from a real end-to-end source-mapping UX

## Core Direction

This work should be owned by `scpp`, implemented in PHP, and treated as a normalization pipeline.

Compilers and build backends are raw diagnostic producers.
They are not the final user-facing diagnostic surface.

That means:
- `g++` output is an input
- `clang++` output is an input
- `ninja` output is an input
- launcher output such as `sscache` is an input
- `scpp` must parse, normalize, remap, and render the final friendly error

The task is compiler-dependent either way.
Even if a compiler can emit JSON, the exact format is compiler-specific and still needs normalization.

## Question

Do we already have source mapping for errors back to original `.phs` / `.php` files?

Short answer:
- not as a general system
- there are a few partial building blocks
- today the repo does not appear to have a first-class end-to-end error remapping pipeline

## What Exists Today

### 1. In-band `error_t` can carry file and line

Current runtime `error_t` includes:
- message
- line
- file

Relevant code:
- [runtime/include/scpp/error_t.hpp](/home/alexv/__AI/simple_cpp/simple_cpp_02/runtime/include/scpp/error_t.hpp)

Current limitation:
- this is only a payload shape for `result<T>`-style in-band errors
- it is not a general mechanism for compiler, generator, Ninja, or arbitrary runtime exceptions

### 2. `take(result<T>)` preserves `error_t`

The `take(...)` helper for `result<T>` propagates `error_t` to the caller.

Relevant code:
- [runtime/include/lang/php/support/php_take.hpp](/home/alexv/__AI/simple_cpp/simple_cpp_02/runtime/include/lang/php/support/php_take.hpp)

Current limitation:
- useful only when the failure is already modeled as `result<T>`
- does not help with thrown exceptions, build failures, generator failures, or plain `std::runtime_error`

### 3. Generator internals often know source line numbers

The generator and IR builder already carry line numbers in several places.
Some generator errors already mention `line N`.

Example:
- [generators/php/src/Generator/Generator.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Generator/Generator.php)

Current limitation:
- this is mostly message-level line inclusion
- file path is not consistently attached
- there is no structured source-location object propagated through CLI diagnostics

### 4. Runtime structured JSON errors exist, but not source mapping

Structured runtime JSON is already supported through `SCPP_ERROR_FORMAT=json`.

Relevant code/spec:
- [runtime/include/scpp/runtime_error.hpp](/home/alexv/__AI/simple_cpp/simple_cpp_02/runtime/include/scpp/runtime_error.hpp)
- [specs/runtime/error_handling.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/runtime/error_handling.md)

Current limitation:
- current structured fields are things like message, code, component, operator, details
- there is no generic original-source file/line mapping contract yet

## What Does Not Exist Yet

The repo does not appear to have:
- a generated source-map artifact from `.phs` / `.php` to emitted `.cpp`
- a CLI-level failure classifier that remaps compiler/runtime/backend failures to original source
- a standard structured source-location payload shared across generator, runtime, and CLI layers
- a guaranteed “if error touches generated C++, show original `.phs` / `.php` first” behavior

## Current Conclusion

Point 1 is still open.

More precise status:
- partial primitives exist
- throw/result/runtime paths have some local structure
- generic source error mapping is not already in place

## Primary Goal

Primary goal:
- take a C++ compiler diagnostic that points into generated `.cpp`
- map it back to the originating `.phs` / `.php`
- make the original source location the canonical user-facing location

Secondary goals:
- keep generated `.cpp` location as secondary debug detail
- normalize generator and backend failures into the same model where possible

## Proposed Pipeline

1. `scpp` runs the build and captures raw backend/compiler output.
2. `scpp` detects the producer:
- GCC
- Clang
- Ninja
- launcher such as `sscache`
3. `scpp` parses that raw output into a normalized internal diagnostic shape.
4. `scpp` remaps generated C++ file/line locations back to original `.phs` / `.php`.
5. `scpp` renders a friendly short error using the original source location first.
6. `scpp` saves the normalized/remapped diagnostic for later inspection.

## Normalization Model

The design should use compiler-specific parser adapters feeding one shared internal schema.

Examples of adapters:
- GCC text adapter
- GCC JSON adapter
- Clang text adapter
- Ninja wrapper adapter
- launcher adapter for `sscache`-style prefixes or failures

Example normalized diagnostic shape:

```json
{
  "tool": "g++",
  "category": "compile",
  "severity": "error",
  "message": "invalid conversion from ...",
  "generated_file": "/abs/project/.prism/generated/src/main.cpp",
  "generated_line": 123,
  "generated_column": 9,
  "original_file": "/abs/project/src/main.phs",
  "original_line": 42,
  "original_column": 5,
  "raw": "..."
}
```

This schema belongs to `scpp`, not to any one compiler.

## Action List

### Phase 1: Compiler/backend parser adapters plus normalized schema

Goal:
- define one shared internal diagnostic schema
- parse compiler/backend output into that schema inside `scpp`

Needed fields:
- original source file
- original source line
- original source column when available
- generated file
- generated line when available
- error origin kind:
  - generator
  - compile
  - runtime
  - backend

Owning layers:
- CLI/build contract: [specs/project_build_v1.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/project_build_v1.md)
- runtime structured diagnostics: [specs/runtime/error_handling.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/runtime/error_handling.md)
- implementation owner: [bin/project_services.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/bin/project_services.php)

Notes:
- JSON from GCC is useful when available, but still must be normalized by `scpp`
- Clang should be treated as a text-parsed adapter unless/until a better structured path is intentionally adopted
- Ninja should be treated as orchestration output, not semantic source of truth

### Phase 2: Add emitted mapping metadata for generated C++

Goal:
- allow compiler/runtime/backend failures that mention generated `.cpp` lines to be mapped back to source

Possible outputs:
- per-generated-unit sidecar JSON
- inline generated comments plus a sidecar index
- project-level map under `.prism/generated/` or `.prism/cache/`

Recommended first version:
- per-generated-unit sidecar JSON

### Phase 3: CLI remapping pass

Goal:
- when compiler or runtime output mentions generated files, the CLI should resolve original source and foreground that first

Likely implementation surface:
- [bin/project_services.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/bin/project_services.php)

### Phase 4: Improve generator-side failures

Goal:
- make generator/input/lowering failures always include original source file and line in structured form

Likely implementation surface:
- [generators/php/src/Support/S2SException.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Support/S2SException.php)
- generator error construction sites

Reason:
- generator failures should fit the same normalized source-location model even though they do not require generated-C++ remapping

### Phase 5: Integrate with saved diagnostics

Goal:
- `last_error.json` should always prefer original source location when known
- generated-file location can remain as secondary detail

## Priority

Recommended order:
1. compiler/backend parser adapters plus normalized schema
2. generated C++ mapping artifact
3. compile-output remapping in CLI
4. structured generator/input source locations
5. runtime failure source enrichment where feasible

## Practical Takeaway

If we start this work now:
- we should not assume there is already a hidden generic mapping layer to hook into
- we should treat this as a compiler-adapter and normalization problem owned by `scpp`
- the friendly error must come from `scpp`, not directly from compiler or Ninja text

The only clearly existing source-carrying path today is still the narrow in-band `error_t` model, plus some ad hoc generator line-number text.
