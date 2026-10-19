# Remaining accounting template exposure
Doc Status: supporting

Audit of production output from `15612367`, after the output/key boundary fix.
All 16 application translation units were preprocessed with their saved Clang flags,
removing the runtime PCH import so header text was visible. Layout includes were
retained. No generated source or production code was modified.

Every unit contains 3,826 lines matching `template` followed by `<`: 139 from
runtime/UTF headers and 3,687 from toolchain headers. No matching template declarations
originate in generated app/type/concrete headers. This textual measure is neither an
instantiation count nor an exact C++ parser-based proof of template absence. Application
uses still instantiate templates, including 44 distinct `shared_p<record_...>` types.

Largest textual contributors include `type_traits` (586), `tuple` (252),
`shared_ptr_base.h` (151), `stl_function.h` (147), `stl_iterator.h` (144),
`stl_algobase.h` (142), `iterator_concepts.h` (126) and `optional` (110).
The runtime origins include `detail.hpp`, `memory.hpp`, shared/unique/weak/value
headers, shared conversion and UTF support. Do not rank optimization benefit from
these counts alone.

The structural gaps are recorded in the [proposed contract extension](../../prelower_template_free_accounting_2026_10_19.md).
`summary.json` records per-unit origins; `run.py` is the reproduction procedure and
requires the previous `/tmp/accounting-header-fix` publication/cache and
`/tmp/accounting-parallel/logs/commands.json`. Raw preprocessed files and command logs
remain in `/tmp/accounting-template-audit`. This was a read-only preprocessing audit,
not a new compile-time or application correctness measurement.
