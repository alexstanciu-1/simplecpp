# Builtin contract: process_close
Doc Status: normative
Status: experimental

`process_close(process_handle $handle): result<bool>`

Terminates unfinished work, reaps the child and invalidates all handle aliases.

The [process family contract](../process.md) defines launch and cleanup ownership, errors, timeout ordering, Linux scope, module
availability and result fields for this builtin.

Implementation and validation: `runtime/include/modules/process/`, `scpp/process.hpp`,
`tests/runtime/native/test_process.cpp` and `tests/tools/test_scpp_process.py`.
