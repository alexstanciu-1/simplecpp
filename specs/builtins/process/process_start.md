# Builtin contract: process_start
Doc Status: normative
Status: experimental

`process_start(string $executable, vector<string> $args, string $input, int $timeout_ms, string $cwd = ""): result<process_handle>`

Launches a batch tool with literal arguments and file-backed binary streams.

The [process family contract](../process.md) defines launch and cleanup ownership, errors, timeout ordering, Linux scope, module
availability and result fields for this builtin.

Implementation and validation: `runtime/include/modules/process/`, `scpp/process.hpp`,
`tests/runtime/native/test_process.cpp` and `tests/tools/test_scpp_process.py`.
