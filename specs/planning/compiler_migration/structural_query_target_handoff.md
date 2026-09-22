# Structural-query construction blocker: v0.1 handoff
Doc Status: planning

The PHP query adaptation passes 27,560 frozen-source result/error comparisons and
39 retained parser/semantic/template/backend-preparation fixtures. Whole-file
conversion passes with the actual same-namespace trait. Native compilation is
blocked by class-name handling. The ready count stays 36 and the target pin stays
2f0d667f. No target code or generated C++ was modified.

## Reproduced failures

1. Existing method and record names coincide, e.g. Syntax_Access::function_parts()
   returns a parse\function_parts. With `new function_parts(...)`, emitted C++ uses
   `create<function_parts>(...)` inside that same-named method and fails to identify
   the type. Twelve construction sites fail this way in the pinned-target proof.
2. Explicit PHP `new \parse\function_parts(...)` passes through the converter
   unchanged, but the native target rejects it inside namespace parse:
   `Qualified self-reference construction is rejected ... use function_parts or \parse\function_parts.`
   Thus the diagnostic recommends the spelling that was already authored.

Both qualified-form tests use strict mode and normal STAN. The rejection occurs on
2f0d667f38a35ff02ef77e813f409189cba2d032 and the new #233 candidate
[d493525d2732e32edaf6b36a251efb05ccb66d6f](https://github.com/alexstanciu-1/simplecpp/commit/d493525d2732e32edaf6b36a251efb05ccb66d6f).
The candidate is therefore not adopted for this component. This does not invalidate
its separately reported inheritance fix.

Inspection points to validateExprTree's NEW-name check in Generator.php: it tests
the AST name text for a leading backslash, without using the absolute-name flags.
The parser represents absolute qualification in flags; the candidate's renderClassName
already consults flags. This is a diagnosis to verify in the v0.1 workspace, not
permission to modify its implementation in the migration workspace.

## Requested target behavior

Accept explicitly absolute same-namespace construction and preserve the qualified
C++ type identity even when a method has the same spelling. Retain rejection of
actually relative duplicated-namespace spellings where the existing contract requires
it. Cover unqualified ordinary construction, explicit same-namespace and cross-namespace
construction, method/type name collisions, and the existing #233 inheritance/catch
regressions. An immutable candidate is needed for downstream proof.

Renaming shared compiler query APIs or adding forwarding factory classes solely to
bypass this target defect is not proposed. Existing record ownership and contracts
can remain intact with truthful class qualification.

## Evidence

`results/structural-queries-01/` contains:

- unqualified-pinned: C++ create<T> failures, full input hashes and logs;
- qualified-pinned: qualified-form validation rejection and staged source;
- qualified-candidate: the same rejection on d493525d, with exact target revision;
- minimal-source: six complete production dependency files and the focused query
  fixture, with managed imports, for a fresh standalone reproduction;
- summary.json, retained.json and oracle.stdout.log: source hashes and host proofs.

The focused fixture needs no filesystem effects. From the repository root, convert
minimal-source into a fresh temporary output, install the base native framework,
initialize a strict project with clang++-18 and runtime.modules=[], and run through
the candidate CLI. Keep PHP execution/bootstrap outside the converted source tree.
The cumulative failure logs additionally retain the original scanner-enabled project.

The construction requirement is now tracked in [#235](https://github.com/alexstanciu-1/simplecpp/issues/235).
The v0.1 regression/release workflow is tracked separately in
[#236](https://github.com/alexstanciu-1/simplecpp/issues/236).
By user direction, this workspace owns downstream portability proofs; the v0.1
workspace owns the fix, release notes, skill review, merges and tagging.
Public reports omit local workspace paths and internal evidence details.

## Latest #233 update checked

The follow-up [combined candidate 361b1e97](https://github.com/alexstanciu-1/simplecpp/issues/233#issuecomment-5771258631)
adds fs_is_windows and Linux fs_read_snapshot after d493525d. The generator's
construction validation is unchanged. A fresh standalone strict project using
minimal-source reproduces the same qualified-construction rejection on
361b1e9752817cd5924a9117fba806c5928bb406 (exit 3); see qualified-latest-candidate.
The filesystem additions can be integrated independently. Their PHP framework
adapters and migration proofs remain work; the current pin is unchanged.
