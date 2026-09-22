# Fixed source-read selection
Doc Status: supporting

Source_Read_Selection owns the pure selection algorithm formerly private to
Source_Reader. The phase delegates to this owner; it still controls lifecycle,
worker execution and result acceptance. This extraction introduces no new compiler
behavior or scheduling model and adds no converter capability.

Selection preserves source order. Full rebuild selects every live file; incremental
selection chooses only missing buffers, independently of pending semantic work.
Deleted rows are skipped, moved rows remain rejected, and tasks capture the selected
file ID/path/mtime/byte size without retaining mutable file records.

PHP/native proofs cover empty input, full/incremental membership and order,
tombstones, unsupported moves, pending work with a retained buffer, unchanged inputs,
and immutable task versions after source metadata changes. Retained snapshot and
lexical-update tests now call the actual selection owner rather than reflecting the
former private method. The production phase exercises the same owner.

Evidence: `specs/planning/compiler_migration/results/read-selection-01/summary.json`.
The selected target remains `2f0d667f38a35ff02ef77e813f409189cba2d032`.

Remaining source-reader dependencies include the filesystem read/version contract:
regular-file checks, open-handle/path device and inode identity, bounded byte reads,
post-read version checks and guaranteed handle cleanup. These must be proved before
migrating Snapshot_Reader and the complete phase. This selection proof does not
establish native filesystem race handling or whole-pipeline portability.
