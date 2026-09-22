# Source paths and directory discovery
Doc Status: supporting

The second rewrite stage owns `read_sources\Source_Discovery::discover(Project_Manifest)`.
It returns a fresh `Source_Listing`: canonical roots, ordered `Source_File` observations
(path, relative path, root index, mtime and size), and entry position. Load
`compiler/bootstrap.php` for host PHP composition. Inputs and earlier results are not
mutated. Positions are local to this listing; they are not persistent file IDs.

## Selection contract

Resolve configured paths against the manifest directory. Canonical roots must exist,
be directories and be disjoint (including aliases and ancestor/descendant roots).
Adjacent names such as `src` and `src2` do not overlap. A configured root may resolve
through a symlink; symlinks encountered inside it, including broken links and links
with ignored extensions, fail discovery. Queued directory paths are checked again.
These observations do not protect against concurrent filesystem replacement.

Traverse roots in selection order, directory entries in the filesystem facade's
sorted order, and directories breadth-first across roots. Select case-sensitive
`.phs` regular files, including hidden files; traverse hidden directories too. Ignore
other ordinary files. Source-suffixed nonregular files fail. Record fresh metadata
without reading contents. Resolve the entry after scanning and require membership.
Single-source manifests select only their resolved entry, without sibling traversal.

Host path spelling uses `fs_is_windows()` on the execution host. Windows separators
are normalized only for Windows semantics; POSIX backslashes remain literal bytes.
Pure classification/normalization takes an explicit host flag so both branches can
be tested on Linux. This does not constitute native Windows filesystem evidence.
The policy follows the prototype's rooted/drive/UNC spelling rules.

Non-goals: stable IDs, changed/deleted reconciliation, cached incremental discovery,
worker scheduling, verified reads and source content buffers. Each call is a complete
fresh scan. Future incremental selection must agree with this clean result. This
stage does not pretend that matching mtime/size proves unchanged content.

## Implementation choices and limitations

Four production files retain the prototype's `read_sources/` ownership layout:
`utilities/paths.php`, `data/listing.php`, `scan.php`, `main_discover_sources.php`.
A single synchronous owner replaces the former Step/Join publication lifecycle.
Typed vectors and named rows replace ad-hoc result arrays. Rows are ordinary classes
because the portable scalar-record grammar does not yet admit their strings and
signed metadata; no compact native layout is claimed. The breadth-first queue retains
visited directory tasks until completion. Profile row/queue memory before a later
layout or traversal-memory optimization.

The framework adds only the host-fact mapping; scan/stat/path helpers already existed.
No converter grammar was expanded. A negative property default was rejected by the
checker; the discovery owner instead explicitly initializes its missing-entry sentinel.
The first native build exposed a case-only collision between the schema wrapper
`scpp\Json_Node` and runtime `json_node`. Renaming the wrapper to `scpp\Json_View`
removed the STAN failure; the manifest stage is revalidated with that shared change.
Use distinctive wrapper names rather than case-only variants of target type names.

## Proof and iteration evidence

The stage proof checks 31 initial outcomes and 31 outcomes after adding/removing files
and changing metadata, using the same generated executable. Expected selections and
ordering are independently specified. It covers nested traversal, aliases, overlap,
entry exclusion, special files, symlinks, literal POSIX backslashes, UTF-8 names,
host classification, repeat calls after failures and single-source selection.
Refresh runs start new processes; they do not independently prove every possible
same-process filesystem cache transition. Existing facade cache-refresh proofs remain
separate evidence. Conversion's unchanged pass reuses all nine staged files.

```sh
python3 compiler/tests/discovery/run.py --results FRESH
python3 tools/php_portability/validate.py --results FRESH \
  --native compiler --target-checkout /tmp/scpp-json-240-probe
```

Exact target: `9b4b33f35f053b487e018c94d6a4a7888d77c64a`, clean unreleased #240 candidate.
See [saved evidence](../planning/compiler_migration/results/discovery-rewrite-01/README.md)
for command/phase timings and native attempts. Count attempts to first pass separately
from corrective cycles and final verification builds; checker-only failures are not
native attempts. Active readiness is now seven production files (manifest + discovery),
not whole-compiler convertibility. Next: verified source reads.
