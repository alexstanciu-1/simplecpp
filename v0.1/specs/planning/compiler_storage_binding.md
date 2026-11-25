# Compiler collection binding prerequisite
Doc Status: planning

## Verified boundary

Native helper commit: 7eee1b79309c2e0d7d17de30e0a84b484a642ebf (PR #243).
The native wrappers passed three focused fixtures and PHP/native trace comparison.
They do not establish PHS source support.

On that exact checkout, calling
`Scpp\S2S\Lowering\TypeMapper::mapDeclaredType()` produces:

```text
Unsupported explicit type syntax: Storage<Row>
Unsupported explicit type syntax: Keyed_Storage<Row>
```

The probe loads `generators/php/src/Support/S2SException.php` and
`generators/php/src/Lowering/TypeMapper.php` and invokes the mapper once for each
spelling. Its generic-syntax allowlist excludes both types. Local portability
`Container_Type` also accepts only vector/hash generic annotations. The configured
compiler target remains 9b4b33f35f053b487e018c94d6a4a7888d77c64a; it was not changed
merely to make annotations parse.

## Proposed ownership split

1. Native/source binding owner: recognize the two explicit collection families;
   register the compiler module/header; map record T without double shared wrapping;
   adapt source integer/string carriers; bind construction, methods, subscript
   read/write/append, isset/unset, count and foreach with keys. Preserve shared
   collection identity, holes, insertion order and retained record handles.
   Prove a strict PHS consumer compiled and executed against the new wrappers.
2. PHP portability owner: parse adjacent Storage<T>/Keyed_Storage<T> declarations
   at fields, locals, parameters and returns; emit supported explicit construction
   and operations. Keep this structural, without inferring receiver types or
   inventing a second semantic type checker. Prove executable PHP/generated PHS
   agreement, including static roots and nested collections.

Do not emit unsupported target syntax and call it completed binding. Do not replace
object collections with vector/hash values just to pass the converter: assignment,
iteration keys and deletion behavior would change. Keep host PHP helpers outside
converted inputs; a general no-export directive is separate work.

The change crosses native type/module/operation owners plus the portability
converter. Under AGENTS.md, confirm that scope before implementation. The smallest
next deliverable is native source binding in the existing #242 workstream, then
portable-PHP binding against its exact proved revision. Validation must cover
construction, alias edits, key checks, holes/order and PHP/native parity; native
runtime fixture success alone is insufficient. No broader generic-type system,
compiler stage rewrite or layout optimization is required.

## Implementation checkpoint

PR #244 candidate d8ddde93 adds native source bindings. Local executable-PHP
conversion now handles explicit template declarations and annotated construction;
see ../portability/storage_collections.md. The older verified boundary above is
historical evidence for PR #243, not the current converter capability. No target
pin change or native compilation was performed in this conversion-only pass.
