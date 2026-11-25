# Mining the v0.1 archive

Use the source, tests, expected outputs, compatibility inventories and reconciliation reports here to compare features with the active compiler. The historical experiments also retain their drivers, patches and manifests.

The language-verification checkpoint logs, loose run transcripts in the planning results, and generated C++ before/after mirrors from the compile-latency experiments are available in the pre-cleanup tree at `5bd4ea43644bfb77e4938333f2119b795e559bbc`. To replay an experiment that expects those files at their original paths, create a separate worktree from that commit.

```sh
git worktree add --detach ../simplecpp-verification-archive 5bd4ea43644bfb77e4938333f2119b795e559bbc
```

To inspect a single historical file, use `git show 5bd4ea436:v0.1/path/to/file`. Evidence bundles containing source probes, scripts or fixtures remain available for mining. [Language verification report](my-try/docs/planning/language_verification_report.md)
