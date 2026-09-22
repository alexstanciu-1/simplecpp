# Simple C++ compiler rewrite
Doc Status: supporting

This is the single active home for the stage-by-stage convertible-PHP rewrite.
The reset is complete; **zero production files are currently ready**. The first
component is project-manifest reading, followed by paths/discovery and verified
source reads. No active compiler CLI or complete compilation pipeline exists yet.

`src/` preserves the prototype's numbered stage layout. `src-runtime-preparation/`
is reserved for later continuation. `tests/` will hold new stage outcome proofs.
Do not populate these folders by copying the whole old implementation back.
Bring reusable code in deliberately, applying the portable-PHP and strict skills.

The converter, PHP runtime framework and reusable capability tests remain at
`tools/php_portability/` and `tests/portability/` from the repository root.
`portability.json` is the empty active ready set. `tools/portability_target.json`
retains exact tested candidate `a1a1babd07082d9abf7ac885b2328c99368ad4cf`.
Other retained toolchain configuration is historical provider/tooling input, not
proof that the rewritten compiler can run it already.

Run framework validation from the repository root:

```sh
python3 tools/php_portability/validate.py --results /tmp/scpp-rewrite-check-NEW
```

It reports framework results separately from compiler readiness. Requesting a
compiler native proof fails explicitly until a real component proof is installed.
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
