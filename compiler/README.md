# Simple C++ compiler rewrite
Doc Status: supporting

This is the single active home for the stage-by-stage convertible-PHP rewrite.
The first four rewrite stages, **input preparation and tokenization (14 production
files)**, pass PHP/native outcome proofs. Next is the parser. No active compiler CLI or complete compilation pipeline exists yet.

`src/` preserves the prototype's numbered stage layout. `src-runtime-preparation/`
stays PHP as-is for now, outside this conversion scope. `tests/` holds registered stage outcome proofs.
Do not populate these folders by copying the whole old implementation back.
Bring reusable code in deliberately, applying the portable-PHP and strict skills.

The converter, PHP runtime framework and reusable capability tests remain at
`tools/php_portability/` and `tests/portability/` from the repository root.
`portability.json` lists the fourteen proved input/tokenization files. `tools/portability_target.json`
pins tested, unreleased #240 candidate `9b4b33f35f053b487e018c94d6a4a7888d77c64a`.
Other retained toolchain configuration is historical provider/tooling input, not
proof that the rewritten compiler can run it already.

Run framework validation from the repository root:

```sh
python3 tools/php_portability/validate.py --results /tmp/scpp-rewrite-check-NEW
```

It reports framework results separately from compiler readiness. Add `--native compiler --target-checkout TARGET` for the registered stage native proof.
See [manifest reading](../specs/portability/project_manifest_reading.md) for the API and scope.
Native capability proofs such as `--native records --target-checkout TARGET` remain
available. No empty compiler build is treated as a successful compilation.

## Preserved reference

The previous adopted compiler, its tools, tests, examples and documentation are
frozen under [reference/pre-rewrite](reference/pre-rewrite/). This is source reference,
not a second maintained implementation. Its internal relative launch paths are
historical; replay it from Git branch `v0.2/pre-rewrite-reference` at commit
`623402d05e066bb5bef12c1439472a0a7f376b10` in an isolated checkout when necessary.
The external prototype checkout is unchanged.

The old 39-file cumulative proof remains historical evidence and reusable test
material, not coverage of this new source tree. See the
[reset record](../specs/planning/compiler_migration/rewrite_reset.md) and
[current methodology](../specs/planning/compiler_migration/README.md#current-methodology-stage-by-stage-rewrite).

Root repository specs and working rules remain authoritative. Preserve meaningful
language/protocol results while allowing better internals; complete migration before
adding compiler functionality. Unions and deeper layout tuning are deferred unless
needed by a selected component.

See [source discovery](../specs/portability/source_discovery.md) for path policy,
selection outcomes and native iteration counts.

[Verified source reads](../specs/portability/verified_source_reads.md) supplies owned
source bytes with explicit version-check limits and PHP/native boundary differences.

[Tokenizer](../specs/portability/tokenizer.md) records reused unit cases, compact rows
and the explicit lexical-failure contract.
