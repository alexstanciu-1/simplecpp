# Source directory scanner
Doc Status: supporting

`read_sources/scan.php` now runs on PHP and native PHP++ against actual filesystem
fixtures. It retains the originating task, observes one selection, and returns
files/subdirectories without assigning global IDs or scheduling recursion.

Directory discovery keeps sorted entry order, excludes dot entries, separates
subdirectories and accepts only the source suffix. Explicit selections preserve
caller order and duplicates, allow non-source suffixes and never recurse. An empty
explicit selection remains distinct from directory discovery, including for a
missing directory. Symlink roots/entries and selected non-files retain rejection.
The worker now calls the already-owned pure Source_Path_Syntax directly for joining
and suffix testing; no path-resolution policy is added.

## Filesystem facade

Uniform imports add `fs_is_link`, `fs_is_dir`, `fs_is_file`, `fs_scan`, `fs_size` and
`fs_mtime`. PHP observations clear the stat cache before use. Native predicates use
existing target operations; scan/size/mtime bridge checked errors to false results
so the compiler continues to own its diagnostics.

- Predicates return bool.
- `fs_scan` returns a dense list/vector of sorted actual names, excluding `.`/`..`,
  or false. Use `take_false($entries, fs_scan($path))` with an explicit string vector.
- Size/mtime return int or false; zero is successful. Failed extraction leaves the
  caller's output unchanged. These facade wrappers deliberately differ from the
  native bare APIs' result<T> shape.
- Paths in this slice are non-NUL filesystem spellings, not Unicode text.
  The Linux proof does not establish Windows behavior or filesystem race immunity.

Assemble with `install_native_runtime.php OUTPUT --filesystem` and enable the
native `filesystem` module. The option composes with `--os`, whose process/lock
selection is unchanged. The new support artifact has its own assembly-manifest
hash; no unused filesystem implementation is forced into other native proofs.

This is discovery metadata, not an atomic snapshot. Snapshot_Reader still requires
its lstat/fstat identity/version/close guarantees. The scanner does not weaken or
replace those checks with these smaller helpers.

## Evidence

The native witness uses real sorted files, an ignored suffix, a subdirectory,
Unicode spelling, exact fixed mtime and size (including zero), selected ordering,
empty selections, missing-directory failure, symlink roots/entries and failed-size
output preservation. It checks task identity. Host fixtures are created by
`tests/portability/filesystem_fixture.py`, outside converted source.

The frozen original scanner additionally matches eleven host scenarios, including
non-UTF-8 names, duplicate selections, selected missing/non-file paths, fresh metadata
after a file rewrite and unchanged retained results. Existing discovery/scan-task
fixtures remain in the cumulative retained compiler suite.

Evidence: `specs/planning/compiler_migration/results/source-scanner-01/summary.json`.
Strict build/STAN, PHP/native independent expectations, assembly/regression checks
and twenty-one retained compiler fixtures pass on
`2f0d667f38a35ff02ef77e813f409189cba2d032`. The frozen scanner oracle passes separately
and is now part of future fast validation runs. Thirty-five production files are ready.
