# Concrete source fields, signatures and ordinary member emission
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `731c4c28` plus
this slice's generator configuration, engine selection and proof changes.

The existing C++ lowering configuration can now select concrete type substitution
for ordinary generation with `prelower_templates = true` and
`concrete_type_substitution = true`. This temporary API selector takes precedence
over the older `runtime_template_abi` comparison selector. It must be consolidated
when equivalent coverage permits retiring the old paths; no new CLI flag was added.

The same engine selection applies to live rendering and retained-fragment replay.
Source fields, method/function signatures, dispatch declarations and bodies therefore
consume the same physical bindings. Ordinary member emission itself required no new
field/call algorithm: the existing emitters already consume prepared facts. There is
no family-specific engine code, semantic pass, AST rewrite or duplicate registry.

## Source proof

The fixture now compiles real PHS declarations such as:

```php
struct Inner { public vector<int> $amounts; }
struct Record {
 public Inner $inner;
 public vector<vector<int>> $nested;
 public parcel<vector<int>> $parcel;
 public vector<receipt> $tracked;
 public function replace(const vector<int> &$input): void {
  $this->inner->amounts = $input;
 }
 public function copy_amounts(): vector<int> { return $this->inner->amounts; }
}
function method(Record &$record, const vector<int> &$input): void {
 $record->replace($input);
}
function &field(vector<int> &$values): vector<int> { return $values; }
function reference(Record &$record): void {
 $values =& $record->inner->amounts;
 $same =& field($values);
}
```

Generated source fields and signatures name the concrete representations; generated
field access and source calls use ordinary C++ syntax. Native integer/vector template
spellings must not occur in the generated source artifacts. The independently
registered `parcel` exercises the same binding path as the runtime containers.

The harness compiles the compiler's actual published support artifacts and verifies
them against the demanded specialization set. It replaces only executable entry
code with an instrumented C++ driver. Container seeding/mutation and native unwrap
adapters remain fixture support, not compiler-generated runtime forwarding methods.

## Validation

```bash
python3 compiler/my-try/tests/prelower_substitution.py --source-fields --results FRESH_DIRECTORY
python3 compiler/my-try/tests/prelower_expressions.py --constructors --results FRESH_DIRECTORY
```

The concrete-source proof passes `-O0`, `-O2`, and ASan/UBSan with optional copy elision
disabled. No PCH or native debug symbols; LeakSanitizer disabled. Native/concrete
counter strings match exactly (allocations, bytes, frees, constructions, copies,
moves, destructions):

```
workload     15 832 15 2 4 1 7
references    0   0  0 0 0 0 0
copy-failure  5 328  5 0 1 0 1
```

This covers nested source fields, source member invocation, returned container values,
reference forwarding/aliases, actual C++ reference identity, record copy/move and
throwing-copy cleanup. Payload liveness assertions remain enabled.

No-op generation preserves fragment identity and output. A source method-body edit
preserves an unrelated function fragment; restoring the body restores exact output.
Switching lowering off/on restores output without changing preparation version.
Three dependent layout batches reuse on warm builds and body-only entry edits;
alignment edits invalidate layout work, and content restoration reuses objects,
headers and the executable. Full cache results are in [summary.json](summary.json).

Focused PHP reference/method/constructor/runtime-type suites and the portable-PHP
checker pass. The retained constructor-transport regression passes, including its native,
sanitizer and method/layout-edit cache checks. The source-field proof took 58.29 s;
the retained-path regression took 176.37 s. Shared runner uses 12 slots with CLI fallback because FPM sockets are
unavailable. This is generated-program validation, not a native compiler rebuild.

Evidence: `/tmp/prelower-fields-final`, `/tmp/prelower-fields-checks`, and
`/tmp/prelower-fields-legacy-regression`. An initial fixture attempted reference
forms that preparation correctly rejected; the proof now stays within admitted
semantics. A superseded intermediate run overlapped fixture edits and therefore
changed link ordering; the final run uses stable fixture contents throughout.

## Remaining limits and cleanup

This proves ordinary source member emission for bounded inline records. It does not
complete runtime-container forwarding (`amounts.append(...)`), concrete ownership
access interfaces, source payload template arguments, recursive layout graphs,
provider compatibility or whole-accounting coverage. Those remain integration work.

Reference-return contracts still require the exact parameter storage type, and
heap-backed property borrows (including the attempted `$this` case) remain rejected
by shared preparation. This slice does not change source reference/lifetime semantics.

The existing source-call/constructor transport and accounting recipes remain intact:
this proof does not yet establish equivalent coverage for removing them. Keep the
engine, lifecycle generator, prepared facts, binding registry and native cache owners.
