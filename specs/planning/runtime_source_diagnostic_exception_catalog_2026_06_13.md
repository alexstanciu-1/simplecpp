# Runtime Source Diagnostic Exception Catalog
Doc Status: planning

Date: 2026-06-13

Purpose: catalog runtime-owned exception paths that affect source-first diagnostics, especially for `.jss -> PHS -> build/run` flows. This keeps runtime attribution work separate from STAN semantic work and from JSS frontend lowering.

This is a planning artifact, not semantic authority.

## Ownership Rule

Runtime owns failures that happen after a program has built and started executing.

For a source-first runtime diagnostic to be good, the runtime exception path should provide, or allow recovery of:

- a structured error code
- operation/component where useful
- generated source location or source location details
- exact runtime message
- optional compact trace

The project/CLI layer may render and remap those details. It should not infer semantic truth that belongs to STAN, and JSS should not add local semantic checks just to hide a runtime-owned missing location.

## Current Runtime Reporting Shape

The runtime has two broad exception families:

| Family | Current diagnostic behavior | Source-first quality | Owner action |
| --- | --- | --- | --- |
| `scpp::runtime_error` with generated/source details | emits structured JSON and can remap through generated line maps | good when details/recovery exist | keep expanding this path |
| `scpp::runtime_error` without recoverable location | emits structured JSON with code/message/operation, but source line may be absent | partial | attach generated/source details at throw site or improve trace recovery |
| plain `std::runtime_error` | runtime JSON can preserve only the raw message for generic exceptions | weak | convert high-value user-facing paths to structured runtime errors |

## Runtime-Owned Exception Catalog

| Area | Example path | Current throw shape | User symptom | Runtime-owned improvement | Priority |
| --- | --- | --- | --- | --- | --- |
| JSON decode parse errors | `runtime/include/modules/json/json.cpp` parser error such as malformed `{...` | `std::runtime_error("json error at byte ...")` | project helper can show `Runtime error while running the built program` plus exact message, but not `main.jss:<line>` | convert decode failures to structured `scpp::runtime_error` or wrap the helper boundary so generated/source location is attached | P0 |
| JSON decode unicode errors | `runtime/include/modules/json/json.cpp` invalid Unicode code point | `std::runtime_error` | same as JSON parse errors | same structured JSON runtime error treatment as decode parse failures | P1 |
| JSON encode unsupported value errors | `json_encode` non-finite float, weak tables, unsupported object key | `std::runtime_error` | exact message only, no source line unless trace recovery succeeds | convert to structured runtime errors with `operation=json_encode` and source location support | P1 |
| Shared pointer/null member access | `runtime/include/scpp/shared_p.hpp` `operator->` | `scpp::runtime_error` with code/operation, but may lack recoverable source location in helper-run path | source-first wrapper exists, but may report no original source line | improve generated-location recovery or add source/generator location at generated call sites for pointer deref operations | P0 |
| Nullable value access | `runtime/include/scpp/nullable.hpp` | `scpp::runtime_error` | generally structured, quality depends on location recovery | verify remap coverage and add focused tests where source line is missing | P1 |
| Unique pointer deref/member access | `runtime/include/scpp/unique_p.hpp` | `scpp::runtime_error` | same risk as shared pointer path | same recovery/location strategy as shared pointer operations | P1 |
| Typed cast failures | `runtime/include/scpp/cast.hpp` | mostly `scpp::runtime_error`; some conversion paths still use `std::runtime_error` | best-covered path today when generated details are present; some edge casts may lose structure | convert remaining user-facing cast `std::runtime_error` sites to structured errors | P1 |
| JSON-to-typed conversion | `runtime/include/scpp/json/from_json.hpp` | `scpp::runtime_error` | likely structured but should be checked against JSS/PHS dynamic normalization flows | add project-level source-first validation for failed typed JSON conversion | P2 |
| Dynamic/mixed operator failures | `runtime/include/scpp/support/mixed_t.cpp` | mixed: structured helper functions plus plain `std::runtime_error` in disabled paths | operator failures often have operation/kind, disabled/reference paths may show only raw message | keep common operator failures structured; convert high-value disabled safe-subset paths if users hit them | P1 |
| Dynamic/hash/vector invalid indexing and mutation | `mixed_t`, `hash_t`, `vector_t` support files | mixed structured/plain errors | exact message may exist, but source mapping may be inconsistent | catalog which operations are structured and add source-first tests for common index/mutation failures | P1 |
| Resource misuse | `runtime/include/core/resource.hpp`, `runtime/include/core/stdio.hpp` | `std::runtime_error` | exact message only for invalid/closed/non-file resources | convert common resource errors to structured runtime errors with helper/operation detail | P2 |
| String helper misuse | `runtime/include/modules/strings/strings.hpp`, `runtime/include/lang/php/support/php_string.hpp` | `std::runtime_error` | exact message only | convert common string helper boundary errors to structured runtime errors if they appear in prototype flows | P2 |
| Datetime representation failures | `runtime/include/modules/datetime/datetime.cpp` | `std::runtime_error` | exact message only | convert to structured runtime errors when dt helper samples become project-level JSS/PHS validation targets | P2 |
| Result-wrapper misuse | `runtime/include/scpp/result_core.hpp` | `std::runtime_error` | exact message only for `.value()`/`.error()` misuse | consider structured runtime errors, but STAN should prevent most misuse before runtime | P2 |
| Debug helper configuration errors | `runtime/include/scpp/support/dbg.cpp` | `std::runtime_error` | operator/debugger-facing raw messages | low urgency; keep as runtime/tooling errors unless they confuse user projects | P3 |
| FastCGI host errors | `runtime/include/hosts/fastcgi/fastcgi_main.cpp` | `std::runtime_error` | host startup/request errors, not normal JSS language failures | host/runtime validation lane, separate from JSS prototype | P3 |

## Source-First Runtime Improvement Order

1. JSON decode parse errors, because they are already in the fs/json/take JSS prototype lane.
2. Shared pointer/null member access location recovery, because the runtime is already structured but source attribution can still be missing.
3. Common dynamic/mixed operator and index failures, because JSS `dynamic` and `js_plus(...)` make these more visible.
4. JSON encode / JSON typed conversion failures, because they are natural next steps for the same prototype lane.
5. Resource/string/datetime helper misuse as sample coverage expands.

## Not STAN Work

These runtime exception improvements should not be solved by STAN:

- malformed JSON text at runtime
- file/resource handle state after execution begins
- non-finite runtime float values passed to JSON encoding
- closed/invalid resource use
- actual dynamic value shape that is only known at runtime
- missing generated/source location attached to runtime exceptions

STAN may prevent some of these statically when values are obvious, but the source-first reporting for runtime failures remains runtime/project-diagnostic ownership.
