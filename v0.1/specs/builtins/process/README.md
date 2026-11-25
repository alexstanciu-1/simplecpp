# Process builtin contracts
Doc Status: normative

This folder contains one contract file per process/CLI builtin in the current Prism++ / Simple C++ PHP surface.

## Current coverage

- `cli_argc`
- `cli_argv`
- `cli_args`
- `shell_exec`

Managed child processes (strict profile, optional `process` module):

- [process_start](process_start.md)
- [process_poll](process_poll.md)
- [process_result](process_result.md)
- [process_stop](process_stop.md)
- [process_close](process_close.md)

The [shared contract](../process.md) owns lifecycle, capture, timeout and inheritance.

See also:

- `specs/builtin_intake_procedure.md`
- `generators/php/specs/php_runtime_symbols_legacy.json`
- `generators/php/specs/php_runtime_symbols_strict.json`
