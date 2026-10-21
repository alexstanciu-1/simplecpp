# Storage registration and PHP-hosted bootstrap checkpoint
Doc Status: planning

Date: 2026-10-21. Candidate: `6fcbed03` plus the Storage registration changes
committed with this note. This is evidence, not a language specification.

## Implemented boundary

The compiler module registers Storage, Keyed_Storage and Key_Storage_List using
ordinary runtime metadata. Inline providers can declare a static construction
factory and implicit default construction; source-record handle formation is
checked in shared preparation. Indexing/iteration and concrete handle/vector
transport reuse existing owners. A shared-header layout scheduling issue and
zero-input runtime factory dispatch were corrected in those existing owners.

No cursor widening, borrowed slots, nested writable container paths or iterator
membership mutation is added. The PHP-hosted compiler has not compiled itself.

## Bootstrap attempt

`/tmp/bootstrap-attempt-20261021-storage.py` snapshots the seven compiler stage/core
directories and the existing native proof driver, converts them using
`tools/php_portability/convert.php`, installs the filesystem/OS/JSON framework,
and runs the PHP-hosted Compiler through Project_Compilation initialize, sync,
prepare, validate and C++ emission. The attempt stops on the first failed phase.
The existing native driver's project configuration supplies the runtime modules.

PHP 8.5 CLI, OPcache disabled, Xdebug disabled for the bootstrap, 4 GiB memory limit;
`/usr/bin/time -v` measures each process separately. No competing test/build task
ran during this measured attempt. PHP heap uses `memory_get_peak_usage(true)`.

| Phase | Wall time | Peak RSS | Outcome |
| --- | ---: | ---: | --- |
| PHP-to-PHS conversion | 6.63 s | 182.9 MiB | 256 PHP files converted |
| PHP-hosted bootstrap | 2.88 s | 126.2 MiB | Parsing rejected parameter syntax |

Framework installation leaves 266 PHS files totaling 1,898,504 bytes. Bootstrap
initialization completes at 192 ms; shutdown records 100 MiB peak PHP heap.
It does not reach semantic preparation or C++ emission, so these numbers cannot
estimate full compiler build time or its eventual peak memory.

The first diagnostic is `Expected parameter type` at
`01_prepare_inputs/module.phs`, byte 405, line 11. The converter emits:

```php
public static function configuration($inputs Storage<module_input>): Keyed_Storage<module>
```

The v0.2 parameter parser expects `Storage<module_input> $inputs`. The same mismatch
appears for other converted generic/nullable parameters, including framework
helpers. This is the next observed blocker; neither converter nor parser is
changed in this slice. Later semantic/backend blockers remain unexamined.

Raw evidence: `/tmp/bootstrap-attempt-20261021-storage/` contains source hashes,
the exact converted snapshot, project/probe configuration, phase JSON, GNU time
reports and diagnostics.

## Focused verification

- `tests/compiler_storage.py`: ordinary and concrete generated programs pass
  with identical output; concrete publication forbids application templates.
  Covers alias mutation, handles surviving removal/replacement, monotonic keys,
  presence/removal, foreach, named/full snapshots and independent default fields.
  `/tmp/storage-v02-full5/` contains the native build/execution evidence.
- Admission, recovery and malformed metadata checks pass, as do type capabilities,
  runtime types/catalog/type bindings, constructors, value/collection probes and
  data construction. Evidence: `/tmp/storage-v02-regression/` (nine PHP suites).
- `tests/prelower_substitution.py --indexing` passes ordinary/concrete parity at
  O0, O2 and with AddressSanitizer/UndefinedBehaviorSanitizer, plus warm-cache,
  body/layout edit and restore checks. Evidence: `/tmp/storage-v02-indexing/`.
- Prelower closure coverage and acceptance-reporting checks pass. The older
  unsupported-boundaries fixture fails at `Missing ordinary representation: local`.
  An isolated `git archive HEAD` base (`6fcbed03`) reproduces that exact failure;
  it is pre-existing and remains untouched. Evidence:
  `/tmp/storage-v02-prelower-contracts/`, `/tmp/storage-v02-base-check2/`.

The shared runner falls back to CLI because this sandbox cannot bind FPM sockets.
These are generated-program and PHP-host checks, not a native compiler rebuild.
