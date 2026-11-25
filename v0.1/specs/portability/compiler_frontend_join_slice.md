# Frontend storage and segmented acceptance
Doc Status: supporting

Frontend_Set stores a typed file-ID map, validates every supplied frontend and
returns nullable shared records. Its nullable constructor input explicitly selects
an empty baseline when absent, matching the token-store authoring pattern.

Frontend_Join retains its segmented protocol. A typed selected-task map and explicit
prepared flag replace the nullable-map sentinel; preparation still validates into a
local candidate before adoption. Each segment validates into a separate typed map
before changing replacements. Failed segments adopt nothing; incomplete finish
retains accumulated work. Completion rebuilds live source order, excludes deleted
files and verifies exact source/token identities before constructing a new set.

Missing membership and nullable result accesses have separate guards. No symbol
resolution or converter capability is added. The existing phase continues using the
same join(), merge() and finish() operations.

The PHP/native fixture exercises invalid segment bounds, incomplete finish, rejected
duplicate segments followed by successful completion, exact frontend sharing,
untouched previous storage, repeated finish, retained frontends, unselected result
rejection, duplicate storage IDs and empty export. Retained frontend-storage/parser
fixtures provide broader host regression coverage, including production callers.

Evidence: `specs/planning/compiler_migration/results/frontend-join-01/summary.json`.
PHP/native validation passes on `2f0d667f38a35ff02ef77e813f409189cba2d032`, as do
all seventeen retained compiler fixtures. Twenty-seven production files are ready.
Structural query owners and the full parser still need migration.
