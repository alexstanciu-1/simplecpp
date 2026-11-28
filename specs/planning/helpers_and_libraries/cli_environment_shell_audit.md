# CLI arguments, environment and shell

Doc Status: planning

Audit of release v0.1 at `d2f63e37e`, my-try at `624ab8a2d`, and v0.2 at `8552bf9f3`. Recommend admitting argument count, an owned argument list and single-name environment lookup. Defer the shell convenience function to a demonstrated compatibility need.

## What the donors actually expose

| Source form | Release v0.1 | my-try |
| --- | --- | --- |
| `cli_argc(): int` | Legacy and strict PHP maps. The executable installs argc/argv before authored code. The count includes slot zero when present. | A zero-argument core catalog call, available inside functions and methods. Generated startup installs the same process context. |
| `cli_argv(): mixed`, `cli_args(): mixed` | Both profiles. Each call builds a new packed mixed table of strings from slots zero through argc − 1. The shorter spelling is an exact alias. Release also supplies root variables `$argc` and `$argv`. | Two core records with the same observable list semantics. The fixture mutates one result and checks both an earlier snapshot and a fresh read, including slot zero. |
| `getenv(name)` | Strict map. The shallow stub says mixed, but the provider returns a copied string or false. Present-empty succeeds. Passing a name with embedded NUL queries the prefix through the C API. | Core catalog returns `result_or_false<string>`. Embedded NUL raises ValueError before lookup. Host name matching and copied bytes are preserved. |
| `shell_exec(command)` | Both maps. Opens a host-shell pipe, captures stdout and returns false on pipe-open failure. The fgets/C-string append path loses bytes around embedded NUL in output. | Core catalog returns `result_or_false<string>`. Counted fread preserves binary stdout. Empty output and nonzero exit both permit successful strings. |

Both shell providers pass the command through a C string, so an embedded NUL truncates the command. Neither reports stderr or child exit status, and neither distinguishes a read error from end of output. The my-try fixture expects a successful empty string for `exit 7`. A missing command can therefore look like a successful command that wrote nothing. The archived my-try tests supply useful contract evidence; the two pinned provider revisions establish where behavior actually changed. [Release profile mappings](intake.tsv) · [my-try core signatures](../../../v0.1/specs/planning/runtime_surface_audit/catalog_core_1.tsv) · [CLI fixture](../../../v0.1/my-try/tests/runtime_cli.py) · [Environment and shell fixture](../../../v0.1/my-try/tests/runtime_environment.py)

## Active v0.2 evidence

Each of the five names failed lookup in a fresh PHP application with general_api enabled. Separate probes rejected the two root variables as unknown locals/globals. A strlen control compiled, and its entry adapter emitted parameterless main. The launcher example has a proof-only native host that receives argc/argv and returns an owned vector of strings including slot zero. Its native test passed during this audit. It supplies a concrete consumer for a general argument-list operation. [Production entry emission](../../../compiler/05_backend/cpp/program.php) · [Launcher host](../../../compiler/examples/launcher/host.cpp) · [Launcher caller](../../../compiler/examples/launcher/main.phs)

The managed Process provider builds a child's literal argument vector, inherits the process environment and captures both output streams. It exposes timeout, signal and exit status. These operations neither retrieve the current executable's arguments nor query one environment value. A Linux program needing shell interpretation can already explicitly launch an installed shell with a command argument and retain the managed result. [Process contract](../../stream_process_curl_modules.md) · [Provider](../../../runtime/src/process/process.cpp)

## Recommended first contract

- **Arguments.** Offer a general integer count and one general `vector<string>` snapshot. Preserve argv[0] exactly as supplied, including a custom invocation name, and retain empty arguments, spaces and byte order. Keep the authored entry body parameterless. A generated entry adapter can pass the raw count and slots to private startup support once before authored code. A count-only query needs no list allocation. Construct owned strings when a list is requested. The core signature and entry support should be independent of the optional child-process archive.
- **Compatibility.** If the donor PHP argument helpers are retained, publish both list spellings as aliases returning packed mixed string snapshots. Metadata should select the PHP or general result directly. Choose distinct general and PHP lookup keys where the same source spelling would otherwise imply different results in an opted-in project. Leave implicit root variables to the PHP-flavor review.
- **Environment.** Offer a one-name read. A present value, including empty, returns owned bytes; absence is a separate state. Use the existing falseable string carrier for PHP getenv. The same carrier could serve the general API without adaptation; the general name and absence spelling remain proposals. Reject embedded NUL before host lookup and use the host's case rules. Environment setters and enumeration have no donor contract in these five calls.
- **Shell.** Defer shell_exec. It brings a second execution contract, including host-shell interpretation and stdout-only results, while managed Process already supports an explicit shell when needed. Revisit for a real PHP migration caller requiring the shorthand. If adopted, specify embedded-NUL rejection, binary stdout, pipe/read failures and the treatment of exit status before publishing it.

A future source-to-executable test should launch with an empty argument and one containing spaces, compare the count and full list across source units, then mutate one snapshot and reread. Use separate subprocess environments for missing, present-empty and nonempty values. Check a NUL-containing lookup against a known existing prefix. Exercise the normal generated entry and verify the count-only caller avoids a boxed/vector dependency. These are proposed acceptance checks for implementation.

## Implementation outcome

The [core publication](../../../runtime/metadata/publication.json) binds cli_argument_count, cli_arguments and env_get. PHP cli_argc and getenv share the general operations. PHP cli_args and cli_argv share the packed mixed snapshot provider. The normal launcher proof now uses the general vector through generated startup. The [source proof](../../../compiler/tests/cli_environment_native.py) verifies cross-unit reads, raw argument bytes, independent results, missing versus empty environment values, NUL rejection, source visibility and count-only header dependencies. Shell execution remains deferred and the PHP-flavor review retains the root variable decision.
