# Iterative expression parsing
Doc Status: supporting

`parse\File_Parser::parse_expression(Lexical_Buffer $tokens, bool $type)` returns an
`Expression_Result` retaining the tokens/source and a compact Syntax_Arena. Valid
results have one root and consume the final EOF. Rejected lexical/grammar input has
valid=false, anchored error start/length/reason, root zero and no published partial
tree. The supported boundary expects complete immutable tokenizer output; this is
not a validator for arbitrary fabricated token vectors.

Three files retain prototype ownership: `data/expression_state.php`,
`handlers/expressions.php`, and `parse_file.php`. Direct same-namespace trait expansion
keeps grammar code on the file parser owner. Its constructor receives initialized
state explicitly; the converter does not synthesize uninitialized named fields.

The shared iterative driver supports names, variables, integer/string/boolean
literals, grouping, named/field calls, template applications, empty-argument named
construction, fields, indexing, addition and less-than precedence. Type mode accepts
named/template type syntax rather than value operators/calls. It retains the
prototype's scoped angle disambiguation and restrictions; it does not resolve symbols.
Explicit continuation/operand/operator stacks replace PHP array references, pops and
multi-level continue. There is no recursive parser call per nested expression.
Frames are mutable ordinary owners; syntax rows remain compact values, accessed
through arena copy/update methods. Logical stack size is distinct from backing storage.

This is expression grammar only. Whole-file statements, functions, structs and
metaprogramming declarations remain future slices. Public static expression entry
creates fresh state for each parse; the private driver can later serve statement/
declaration grammar on the same parser owner. src-runtime-preparation stays PHP as-is.

## Tests and native iterations

The reference oracle invokes the preserved prototype's actual expression method and
EOF expectation through a host-only reflection harness. It reuses grammar forms from
existing parsing, struct and metaprogramming tests; their whole unit bodies require
unmigrated declarations/statements and are not claimed as passing unchanged.
Outcomes compare kind/byte-span/ordered child shape in canonical breadth-first order,
not incidental allocation IDs. Independent literal/addition expectations supplement
the oracle. Invalid outcomes compare anchored byte spans. The suite has 132 outcomes,
including 1,000 nested groups, 1,000 nested calls, 1,500 summed operands and deterministic
combinations. No per-node lexeme copies or PHP/native byte-offset conversions occur.

PHP passed before native compilation. Native attempt 1 failed because local names
operator and template passed through as C++ keywords. Renaming to operation_kind and
is_template cleared attempt 2; all 132 outcomes pass on clean exact target
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Count: two native build attempts,
one corrective cycle. No generated code or target implementation was patched.

```sh
python3 compiler/tests/expressions/run.py --results FRESH \
  --target-checkout /tmp/scpp-json-240-probe
```

See [evidence and iteration accounting](../planning/compiler_migration/results/global-functions-expressions-01/README.md).
The active code uses the new [global helper convention](global_functions.md).
