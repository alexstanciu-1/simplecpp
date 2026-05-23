# Strict Docblock Type Audit
Doc Status: planning

Date: 2026-05-23

Purpose:
- audit repository usage of annotated/docblock data type syntax in places that influence strict-mode authoring
- identify where docs, specs, and examples still normalize legacy typed-docblock forms for `.phs` / strict-mode code
- separate strict-authoring drift from generator-contract docs and test fixtures that still intentionally exercise legacy compatibility

## Audit Goal

Target rule for this audit:

- strict mode and `.phs` examples should not teach or normalize typed docblock declarations such as `$x /** int */ = 0;`

Important distinction:

- this audit is not claiming the generator no longer supports legacy compatibility syntax
- this audit is focused on repo surfaces that train humans and AI agents how to write strict `.phs` code

## Summary

The repo still contains multiple high-impact places where strict-facing documentation or strict `.phs` samples show docblock-typed declarations as ordinary authoring syntax.

Most important finding:

- the problem is not limited to sample files
- onboarding docs, top-level specs, and the strict quick-learn still contain examples that look like recommended strict source
- generator subsystem specs and test fixtures contain far more occurrences, but many of those are documenting or testing compatibility behavior rather than prescribing strict authoring style

## Count Snapshot

These counts come from a repo scan for local variable forms matching:

```text
$name /** ... */
```

Snapshot totals:

- `docs/examples/php/strict`: `7`
- `docs/ai_onboarding`: `3`
- `specs/` excluding `planning` and `_archive`: `30`
- `generators/php/specs`: `54`
- `tests/specs`: `8`
- generated/fixture tests under `tests/generators` and `tests/php-matrix`: `2083`

Interpretation:

- the strict-authoring drift is real in docs/specs
- the very large fixture count is mostly expected compatibility/test surface, not the first cleanup target

## Highest-Priority Findings

These are the most important because they directly influence how strict `.phs` code gets written.

### 1. Strict sample files still use docblock-typed declarations

Files:

- [main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/docs/examples/php/strict/project_samples/strict_curl/main.phs:6)
- [main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/docs/examples/php/strict/project_samples/strict_fs_json/main.phs:2)
- [main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/docs/examples/php/strict/project_samples/strict_regex/main.phs:1)
- [main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/docs/examples/php/strict/project_samples/strict_str_io/main.phs:6)
- [main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_01/docs/examples/php/strict/project_samples/strict_error_paths/main.phs:1)

Current examples still include:

- `$err /** error */;`
- `$fh /** resource_handle */;`
- `$ch /** curl_handle */;`
- `$resp /** curl_response */;`

Impact:

- these are strict `.phs` samples
- an AI agent will treat them as live canonical authoring examples

### 2. AI onboarding style guidance still teaches docblock-typed locals

File:

- [coding_style.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/docs/ai_onboarding/coding_style.md:48)

Examples still show:

- `$count /** int */ = $data["count"];`
- `$id /** int */ = $row["id"];`
- `$name /** string */ = $row["name"];`

Impact:

- this is exactly the kind of short “how to write code here” doc an AI assistant will mirror

### 3. Strict quick-learn still contains live strict-looking docblock examples

File:

- [simple_cpp_php_strict_quick_learn.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/simple_cpp_php_strict_quick_learn.md:642)

It does correctly say the syntax is legacy compatibility at [line 720](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/simple_cpp_php_strict_quick_learn.md:720), but it also still shows current code snippets such as:

- `$err /** error */;`

and retains a legacy-syntax table at:

- [lines 447-462](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/simple_cpp_php_strict_quick_learn.md:447)

Impact:

- the prose is directionally correct
- the examples still leak the old habit into strict-facing documentation

## High-Priority Findings

These are normative or near-normative docs that reinforce the same pattern.

### 4. Top-level semantic specs still use docblock-typed locals in examples

Files:

- [dynamic_types.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/dynamic_types.md:40)
- [array_semantics.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/array_semantics.md:94)
- [canonical_examples.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/php/canonical_examples.md:266)
- [catalog.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/php/catalog.md:125)

Representative examples:

- `$v /** mixed */ = 5;`
- `$x /** int */ = $a["k"];`
- `$files /** hash<mixed> */ = [];`
- `$list /** vector<int> */ = [1, 2, 3];`

Impact:

- these files are high-authority reading surfaces
- even when not strict-only, they still shape what “normal Prism++ code” looks like

### 5. Tester spec examples still normalize docblock types

File:

- [TESTER_SPEC.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/specs/tester/TESTER_SPEC.md:98)

This file contains many typed-docblock examples for simple values and containers.

Impact:

- lower than the strict quick-learn, but still a user-facing spec surface

## Medium-Priority Findings

These matter, but they are more about engine/generator contracts than strict authoring guidance.

### 6. Generator subsystem specs still treat docblock syntax as the canonical typed-slot surface

Files:

- [rules.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/specs/rules.md:86)
- [rules_catalog.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/specs/rules_catalog.md:314)
- [StrLComp.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/specs/StrLComp.md:75)
- [unsupported_dev.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/generators/php/specs/unsupported_dev.md:7)
- [spec.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/runtime/specs/spec.md:317)

Impact:

- these are probably correct from a compatibility/generator-contract perspective today
- but they conflict with the desired strict-authoring message if quoted out of context

Recommendation:

- do not treat these as the first cleanup target unless the language surface is being changed
- instead, clearly label them as generator compatibility/accepted forms where appropriate

## Lower-Priority / Expected Surfaces

### 7. Test-spec docs and fixture tests intentionally exercise docblock typing

Files:

- [generate_php_samples_docs.md](/home/alexv/__AI/simple_cpp/simple_cpp_01/tests/specs/generate_php_samples_docs.md:724)
- generator special tests under `tests/generators/php/special_tests/`
- matrix fixtures under `tests/php-matrix/`

Impact:

- these are not the main source of AI drift unless they are presented as authoring guidance
- many of them exist specifically because legacy typed-docblock syntax is still supported/tested

Recommendation:

- keep them in the audit inventory
- do not mix them into the first strict-doc cleanup wave

## Root-Cause Pattern

The repo currently carries two messages at once:

1. strict `.phs` authoring should move away from docblock-typed declarations
2. many examples across docs/specs still display docblock-typed declarations as if they were ordinary code

That mismatch is enough to train AI agents into the wrong habit, especially because:

- examples are stronger than prose
- short style docs are stronger than deeper caveats
- high-authority specs still show the old forms inline

## Recommended Cleanup Order

### Wave 1: strict-facing docs and examples

- `docs/examples/php/strict/**`
- `docs/ai_onboarding/coding_style.md`
- `specs/simple_cpp_php_strict_quick_learn.md`

Goal:

- remove strict `.phs` docblock-typed declarations from the most imitated surfaces first

### Wave 2: top-level user-facing specs

- `specs/dynamic_types.md`
- `specs/array_semantics.md`
- `specs/php/canonical_examples.md`
- `specs/php/catalog.md`
- `specs/tester/TESTER_SPEC.md`

Goal:

- stop reinforcing legacy syntax in primary semantic docs unless a section is explicitly about legacy compatibility

### Wave 3: subsystem contract labeling

- `generators/php/specs/**`
- `runtime/specs/spec.md`
- `tests/specs/**`

Goal:

- retain compatibility documentation where needed
- label it clearly as parser/generator accepted syntax rather than recommended strict authoring syntax

### Wave 4: optional broader cleanup

- planning docs
- legacy examples
- compatibility fixtures and generated matrix fixtures, only if policy later requires syntax migration there too

## Proposed Policy Clarification

To reduce future drift, the repo should likely adopt an explicit wording close to:

- typed docblock declarations remain a legacy compatibility syntax at the generator/language-support layer
- strict mode and strict `.phs` documentation must not use them as the normal authoring form

## Immediate Follow-Up Candidates

1. finish removing remaining typed docblock declarations from strict samples
2. update `docs/ai_onboarding/coding_style.md` to typed-declaration examples
3. scrub strict quick-learn examples so its live snippets match its “legacy compatibility syntax” statement
4. decide how `error`, `resource_handle`, `curl_handle`, and `curl_response` should be spelled in modern strict source before finalizing sample cleanup
