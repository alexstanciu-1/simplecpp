# Compiler step contract portability slice
Doc Status: supporting

`compiler/src/compile/step.php` is the third ready production file. Its six lifecycle
states and five interfaces remain unchanged except for managed imports. Removing
that block restores the original adopted source bytes.

The structural converter now owns separate interface and method-signature nodes,
and accepts unit enums as well as the earlier literal integer-backed enums.
Interfaces may contain documentary comments and explicit public, zero-argument
method signatures with scalar, void or named return types. Interface inheritance,
parameters, method bodies, mixed returns and dynamic declarations remain outside
this slice. Named return types are emitted without resolving them. Implementations
and signature conformance are the responsibility of PHP and native STAN.

This is a declaration slice, not a migrated step implementation. No lifecycle
execution algorithm or native interface dispatch claim is introduced. The shared
entrypoint proves lifecycle case assignment/comparison; the host harness checks
all original interface names, method names, visibility, argument counts and return
types. The generated project compiles these declarations together with the prior
update-context and tokenizer slices under strict v0.1.76, with STAN enabled.

The cumulative command remains:

```bash
python3 tests/portability/compiler_context/run.py \
  --target-checkout /path/to/clean-v0.1.76-checkout \
  --results /path/to/fresh-evidence-directory
```

[Evidence](../planning/compiler_migration/results/step-03/summary.json) records
PHP/native output, four-file conversion/reuse, unsupported declaration rejection,
five existing compiler fixtures and the exact interface check. Generated files
are preserved under the evidence directory with their source-relative paths.
Method implementations, constructors, typed containers and packed row storage
remain migration work. The original prototype and LLVM provider pin are unchanged.
