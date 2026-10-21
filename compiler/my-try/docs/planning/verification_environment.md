# Verification host and isolated MySQL integration — V-E01 / V-E03
Doc Status: supporting

Closed 2026-10-21, using harness/fixtures/docs only. **51/64 original issues resolved
(79.6875%), +3.125 points**, with 13 open. V-E01 is an evidence-backed environment
reconciliation; V-E03 supplies and verifies the missing integration harness.
Neither is a new source-language capability or a runtime implementation repair.

## Host contract and reproducibility

`tools/verification_host.py` checks requested stages independently:

| Stage | Requirements checked |
| --- | --- |
| baseline | Selected PHP 8.4+ and ext-ast for the v0.1 lane |
| frontend | mbstring for v0.2 tokenization |
| native | pcntl_waitid in the selected PHP and a Clang executable |
| mysql | CMake, pkg-config, MariaDB server/initializer/client and an available Connector/C package |

The preflight records paths, versions and missing requirements; it never installs
packages or alters services. Stage selection avoids requiring MySQL or ext-ast for
a frontend-only run. Production compilation/execution supplements discovery: tool
presence alone is not a native-build pass. The shared runner remains the owner of
FPM selection and documented CLI fallback; no second PHP executor is introduced.

Dev002 used PHP 8.5.11 (ast, mbstring and pcntl_waitid enabled), Clang 18.1.3,
CMake 3.28.3, MariaDB 11.8.9 and libmariadb Connector/C 3.4.10. The FPM/CLI versions
differ, so the shared runner selected CLI fallback. This closes the tested host
configuration, not an arbitrary-host/platform certification or a new baseline run.

## Isolated MySQL fixture

The test-owned CMake entrypoint consumes existing runtime targets and builds only
`test_mysqli_connect`, `test_mysqli_fetch_exhaustion` and the new real-provider
fixture. No runtime CMake or implementation file changes were needed. A compile-time
requirement for `SCPP_HAS_MYSQLI=1` prevents disabled stubs from counting as success.
The two original native test sources are unchanged.

`mysql_isolated.py` initializes a fresh private temporary data directory under the
runner's temporary directory. Server/client commands use `--no-defaults`, a private
Unix socket and no TCP networking; SQL independently checks `@@skip_networking`.
It neither connects to nor reconfigures a system database. The private seed creates
one database/table and a SELECT-only fixture user. Public fixture-only credentials
are used solely within that disposable instance.

The owner waits for server readiness, runs the probe and terminates/waits for its
own child in `finally`, escalating to kill only if shutdown times out. The server
inherits the outer test process group for hard-timeout cleanup. The private data
directory is removed and lifecycle evidence is retained. No daemon or database
files remain after either tested exit path.

## Results

`tests/verification_environment.py` passed its three focused tasks using the shared
12-slot runner on dev002 (native CMake builds used ten jobs alongside the host task):

- **Preflight controls, 0.08s:** actual capability discovery plus missing extension,
  PHP-version, native launcher, executable and connector rejection controls;
  frontend-only stage isolation; an actual CLI invocation with a missing PHP binary
  fails with an actionable diagnostic.
- **Default prelowering, 12.92s:** the unconfigured Compiler API publishes a strict
  program with `application_templates=forbidden`. Production `tools/build_cpp.php`
  compiles eight objects and one layout and links once. The program prints `42`.
  A second build reuses all eight objects and the layout, with no compilation or
  relink; the executable again prints `42`. No PCH or ordinary-mode fallback.
- **MySQL integration, 20.38s:** both previously unrun original native tests pass.
  The supplemental real-provider probe connects to the private database, reads
  two deliberately out-of-insertion-order rows via an ordered SELECT, verifies
  associative and numeric rows, and requires repeated end-of-results nulls.
  Repeated close is safe; wrong credentials yield nonzero connection status and
  a nonempty diagnostic. Assertions avoid server-version-specific error text.
  Both the successful fixture and a deliberately failed post-readiness fixture
  shut down with server exit zero and remove their private directories.

The original connect test remains a weak unreachable-endpoint surface smoke. The
original exhaustion test remains a stub-handle test for row/null/alias behavior.
Only the supplemental fixture establishes real-database behavior. No provider defect
was observed in these bounded checks; failures outside them are not ruled out.

## Evidence and limits

[Acceptance archive](verification-environment-acceptance.tar.gz), SHA-256:
`6a38a1580eaa278f7ddab0afa5414753034fab1c5559fa1cb5d3bf244c15da7d`.
It contains command journals/stdout/stderr, host versions, the prelowering manifest,
CMake configuration/link evidence, both lifecycle records and the intentional
failure diagnostic. Source audit: **5,378 paths matched, zero mismatches** in
`/home/alex-ai/curl/20261020-work`. The [source delta](verification_environment_source_delta.json)
extends the span/hash snapshot; the archive includes the complete manifest.

This does not add strict-PHS MySQL metadata/source exposure. Native MySQL provider
tests do not pass through template prelowering; the separate production host probe
does. Prepared statements, transactions, query-error semantics, TLS, remote servers,
other connectors/platforms and a full database suite remain outside this goal.
No baseline replay, compiler native rebuild, runtime/compiler implementation changes
or full matrix were performed. Historical blocked findings remain in the ledger.
