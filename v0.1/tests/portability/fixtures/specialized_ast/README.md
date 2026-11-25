# Specialized AST contract checkpoint
Doc Status: supporting

Run the bounded proof against an explicit candidate checkout:

```sh
python3 tests/portability/specialized_ast.py --target-checkout .
```

The fixture compares PHP/native output for required nullable facts, covariant
zero-argument object accessors through concrete/base/interface handles, trait-owned
source spans, typed worker calls passing `$this`, and a cursor retaining its source
after the creator returns. Output is `64:17:17:17:64`. The harness also checks native
qualified parent access and rejects an unrelated accessor return through STAN.
This proof keeps the inheritance declarations together, with a separate consumer
unit, and uses the normal STAN-enabled build. The separate
`tests/tools/test_scpp_cross_file_covariance.py` proof splits the ancestors and
overrides across files and checks ancestor-signature cache invalidation.
Advisory STAN diagnostics remain; a successful build is not a claim of zero diagnostics.

This does not prove the production inspection iterator's PHP `Iterator` interface
or generic Storage cursor binding, nor compile the complete my-try compiler.
See the migration audit in `compiler/my-try/docs/planning/`.
