# Builtin contract: process_result
Doc Status: normative
Status: experimental

`process_result(process_handle $handle): result<process_output>`

Returns an independent cached output/status snapshot after completion.

The [process family contract](../process.md) defines launch and cleanup ownership, errors, timeout ordering, Linux scope, module
availability and result fields for this builtin.

Implementation and validation: `runtime/include/modules/process/`, `scpp/process.hpp`,
`tests/runtime/native/test_process.cpp` and `tests/tools/test_scpp_process.py`.
