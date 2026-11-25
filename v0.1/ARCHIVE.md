# Mining the v0.1 archive

Use the source, tests, expected outputs, compatibility inventories and reconciliation reports here to compare features with the active compiler. The historical experiments also retain their drivers, patches and manifests.

The language-verification checkpoint logs, loose run transcripts in the planning results, and generated C++ before/after mirrors from the compile-latency experiments are available in the pre-cleanup tree at `5bd4ea43644bfb77e4938333f2119b795e559bbc`. To replay an experiment that expects those files at their original paths, create a separate worktree from that commit.

```sh
git worktree add --detach ../simplecpp-verification-archive 5bd4ea43644bfb77e4938333f2119b795e559bbc
```

To inspect a single historical file, use `git show 5bd4ea436:v0.1/path/to/file`.

## Feature comparison inputs

The [language verification report](my-try/docs/planning/language_verification_report.md) and its coverage and reconciliation ledgers describe known features and gaps. The [operator matrix](specs/operator_matrix/README.md) defines the cases. The [matrix input catalog](my-try/docs/planning/v0_1_compatibility_operator_matrix_inputs.json.gz) keeps both source variants, expectations and comparison rules for all 22,816 cases in the historical run.

Filtered evidence packages under `my-try/docs/` end in `.inputs.tar.gz`. Each keeps the original relative names of the retained probes, scripts, fixture data and comparison inputs. Use `tar -tzf FILE` to list a package. Command recipes retain arguments and expected outcomes. Historical reports link to the original revision for complete observations and file hashes. Use the worktree above for audit scripts that read the original evidence formats.
