# Parser task selection
Doc Status: supporting

Parser_Selection owns the pure selection algorithm formerly private to Parser.
The phase still owns lifecycle, workers and segmented acceptance and delegates its
selection to the new owner. No scheduling or compiler functionality changes.

Selection first skips deleted files, then requires an exact current source snapshot
behind each token buffer. Full rebuild selects all live buffers in source order;
incremental selection selects missing frontends or changed token-buffer identity.
Equal source contents do not substitute for identity. Full rebuild still rejects
missing/stale token inputs before any worker executes.

Separate null guards replace PHP nullable member/coalescing expression behavior.
Existing converter forms suffice. The PHP/native proof covers warm/full/cold/empty
selection, order, changed token identity, untouched previous frontends, missing tokens,
stale equal-content source identity and deletion. Existing parser/incremental fixtures
continue to exercise the real phase caller.

The structural-query cursor decision and source-diagnostic target blocker remain
separate dependencies. This proof does not mark the complete Parser phase ready.

Evidence: `specs/planning/compiler_migration/results/parser-selection-01/summary.json`.
PHP/native validation passes on `2f0d667f38a35ff02ef77e813f409189cba2d032`, as do
all seventeen retained compiler fixtures. Twenty-nine production files are ready.
