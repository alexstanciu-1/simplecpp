# `json_pull_reader_create`
Doc Status: normative
Status: experimental tooling API
Module: json

`json_pull_reader_create(): json_pull_reader` allocates a fresh non-null shared
reader handle. It takes no arguments and does not start parsing. Allocation
failures retain ordinary exception behavior.

Call `start(string text, string file, int limit): result<bool>` once to initialize
the reader. The reader owns its input bytes; changing the caller's string does
not alter its input. Copying the handle shares cursor state, while another factory
call creates independent state. Neither copying the parser payload nor concurrent
advancement is supported.

The [pull-reader protocol](pull_reader.md) defines the fourteen instance methods,
syntax, errors and terminal failure behavior. Before start, reading returns an
error. A failed start cannot be retried. The portable facade translates Result
errors into its existing exception family; the factory does not change this flow.

The binding lowers to `scpp::json::pull_reader_create` in
`modules/json/pull_reader.hpp`, returning the existing `shared_p` handle. It does
not introduce generic shared-provider constructor support.

Focused binding proof: `compiler/my-try/tests/json_pull_reader.py`. Legacy and
ordinary v0.2 execute the protocol fixture. Concrete template prelowering currently
stops at the existing shared-runtime-member argument sequencing limitation; the
test records that blocker rather than claiming concrete execution succeeds.
