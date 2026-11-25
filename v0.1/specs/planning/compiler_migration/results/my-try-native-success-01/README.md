# Native compiler validation
Doc Status: planning

Fresh normal STAN-enabled build and incremental rebuild passed. All 142 cases
matched PHP byte-for-byte: 48 valid outputs and 94 rejection messages. All 48
native-emitted programs were compiled by clang-18 and executed with expected exits.
The driver also checks repeated compilation, retained output identity, failure
publication, and recovery in the same process. Five PHP helper/model suites pass.

Reproduce from the repository root with an unused results directory:

```sh
python3 compiler/my-try/tools/native_validate.py --target-checkout /tmp/scpp-native-244 --candidate-revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 --results compiler/my-try/build/native-02
```

The target is PR #244 at the named revision plus focused fixes from commit
0c28b96e; candidate.json fingerprints the actual target sources. Apply candidate-overlay.patch with
`patch -p1` in a clean extraction of that upstream revision. source_hashes.json
fingerprints the converted input snapshot including the driver. commands.json
records commands and timings. Full per-case logs and executable remain under
compiler/my-try/build/native-01 (ignored build artifacts).

The driver reads that result directory's request.txt for a module path. It is a
serial validation entry point, not a packaged CLI. The verified target pin remains
unchanged. STAN has zero compile-error-bucket diagnostics but 159 advisory errors
and 33 warnings: see analysis.json. Those require separate review; passing native
compilation/tests does not prove all analyzer diagnostics harmless.

See compiler/my-try/docs/native_adaptations.md for source workarounds to discuss.
