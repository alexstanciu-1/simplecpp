# Integrated tokenizer/IO checkpoint
Doc Status: historical

Collected from `/home/alex-ai/tokenizer-io/20261020-02` on dev002, 2026-10-20.
This is intermediate evidence, not an S11/S13 completion claim.

`integrated_checkpoint.tar.gz` preserves 175 logs, result/check files and authored
production source probes for `runtime-01`, `source-01` through `source-05`, and
`prelower-01` through `prelower-03`. Its internal `checkpoint-evidence.json` lists
SHA-256 hashes; every archived file was checked after retrieval.

Archive SHA-256:
`2e13fdcc05e7cab6b961e64f9c5a1fcde4d044d5a631be7257c2db2712ef0c7d`.

The original tokenizer default-prelowering proof compiled four units and linked
once on the cold build; its warm build compiled and linked zero units. Both
executions matched the original expected output. The integrated runtime and
ordinary-mode source proofs passed, while IO production and take-state probes
retain the failures described in [the active report](../tokenizer_io.md).

The archive is not a final source manifest: backend files and probes evolved
between runs. Final acceptance must freeze and verify the completed source inputs
and rerun the required gates. The isolated remote checkout and previous
`20261020-01` baseline remain preserved.

## Final acceptance archive

`acceptance.tar.gz` contains 1,066 hash-indexed files across baseline, intermediate
and clean final verification. Every indexed file was verified after retrieval.
SHA-256: `01da154fce8be57df41f5f5c242bd5881eeca58b6a123d87c5816630202ad634`.

The final source manifest covers 5,337 input files and matches the dev002 clean
checkout. See [final results](../tokenizer_io_results.json) and
[the closeout report](../tokenizer_io.md). The older checkpoint archive is retained
as historical evidence; it is not the final acceptance result.
