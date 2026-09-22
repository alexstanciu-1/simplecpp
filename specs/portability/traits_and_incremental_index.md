# Direct traits and incremental declaration index
Doc Status: supporting

This checkpoint implements the agreed authoring restriction: traits split a class
into files; a trait may also have several direct consumers in the same namespace.
It does not resume the broader compiler migration or make arbitrary prototype PHP
convertible. The production ready set is still eleven files.

## Authoring contract

- A file has no namespace, or one leading lowercase `namespace name;`. Comments
  and optional `declare(strict_types=1);` may precede it. Qualified namespace names
  are allowed. Bracketed, repeated or late namespace declarations are errors.
- Named classes, interfaces, traits and enums are indexed by fully qualified name,
  kind, file and source location. Duplicate names are rejected case-insensitively.
  Declarations must be unconditional and at file scope.
- A class may use one or more directly named traits. Each trait must be in exactly
  the same namespace as the class, including the global namespace. Unqualified
  local names and fully qualified same-namespace names are supported.
- Traits cannot use traits. No adaptation blocks, `as`, or `insteadof`. Repeated
  uses and method collisions with the class or another trait are errors. No PHP
  override/precedence machinery is implemented.
- This slice admits method-only traits. Trait properties, constants and magic
  methods are rejected. Methods have explicit public/private/protected visibility,
  optional static, explicit scalar/named parameters and returns (including void returns), and bodies
  already supported by the converter. See the later [signature slice](method_signatures.md).
- All files still require the uniform managed function imports. Manual class,
  namespace or function import aliases remain unsupported. Use fully qualified
  external type names; shared namespace alone would not preserve differing aliases.
- Shared traits are allowed, but splitting one owner's implementation remains the
  preferred authoring use. A shared trait edit invalidates all direct consumers.

Example authored PHP, following the normal generated import block:

```php
namespace sample;
trait Counter_Operations {
    public function advance(int $amount): int {
        $this->value = $this->value + $amount;
        return $this->value;
    }
}
```

```php
namespace sample;
class Counter {
    use Counter_Operations;
    public int $value = 0;
}
```

The converter inserts the trait's method tokens into the consuming class, then
uses the same method/body parser as ordinary class methods. It validates standalone
trait bodies too. Original trait file/line attribution is retained for conversion
diagnostics. Native diagnostic source mapping is not added by this slice.

Every input still has a corresponding `.phs` output. Trait declarations do not
appear as native declarations: a trait-only output contains only its surviving
file prologue/comments. A file containing both a trait and a class is supported.
Declaration lookup and direct expansion are the explicit exception to purely
file-local conversion; there is no receiver-type inference, inherited-member
lookup, overload resolution or general semantic compiler.

## Incremental discovery and cache

```bash
php tools/php_portability/sync_imports.php SOURCE
php tools/php_portability/convert.php SOURCE OUTPUT --stats
```

`--stats` adds cache counters; the existing three conversion counters and default
CLI output remain compatible. Import synchronization is a separate authoring step,
not a whole-tree operation run automatically before every conversion.

The output owns these separate artifacts:

- `.scpp-portability.json`: existing source/output fingerprints and publication ownership.
- `.scpp-portability-index.json`: source root, cache version, directory membership,
  file metadata/hashes, declaration locations, direct trait uses and token references.
- `.scpp-token-cache/<fingerprint>.php-cache`: generated PHP `return [...]` token arrays.

The non-source suffix is deliberate: v0.1.76 discovers `.php` files as native project
inputs. PHP `require` and OPcache accept these PHP-format cache artifacts without
a `.php` extension. `return` avoids assignments into the loader's local scope.
CLI OPcache is optional and currently disabled by default in this environment.
The proof enables it explicitly and checks `opcache_is_script_cached`. Persistent
OPcache file-cache configuration across separate CLI processes is not installed or
promised here; the generated token files themselves persist across invocations.

After the initial scan, each invocation checks metadata for known directories and
entries. Unchanged directory membership is loaded from the index; only changed or
recent directories are listed. Added subdirectories are discovered recursively.
Files added or removed change the index and the owned output set automatically.
This is metadata polling, not a watcher or a constant-time change journal.

Stable unchanged files reuse declaration metadata without source reads or
tokenization. Changed files are read and hashed; unchanged content reuses its
tokens. PHP's filesystem timestamps have second resolution, so an observation in
the same second as a file/directory timestamp is conservatively rechecked. Once
an observation is strictly later, subsequent unchanged runs avoid those reads.
Preserving mtime alone cannot hide an edit: ctime/inode/size and the recent-write
check participate. This assumes normal local-filesystem metadata semantics; it
does not promise detection of external changes that preserve every metadata field.

Only files requiring generation load token payloads. Consumers include their
direct trait files' content hashes in the input fingerprint. An unrelated file
addition does not invalidate every output. Changes to conversion rules invalidate
generated outputs; changes to token/index/import policy or PHP version invalidate
the corresponding persisted source metadata and tokens.

Removed declarations disappear from the new index. Removing a used trait without
changing/removing its consumers is an error, and the previous successful output
set remains in place. It is not a successful conversion of the new source set.
Removing the consumers too removes their owned outputs and token artifacts.

## Publication and boundaries

Source diagnostics, dependency/collision checks and cache/output collision checks
precede publication. Files publish individually with replacement by rename;
there is still no whole-project transaction or concurrent-writer guarantee.
Consume generated output only after the command succeeds.

Token artifacts have immutable fingerprinted names and are hash-checked before
execution by `require`. Missing/modified artifacts produce a diagnostic before
loading; remove the index to rebuild missing cache entries. Modified artifacts
with a colliding fingerprint must be removed explicitly. Cache cleanup removes
only verified old artifacts referenced by the previous index, preserving unrelated
files. Symlink source entries and cache paths are rejected.

Generated output fingerprints are still verified to detect and repair edited or
missing output. Avoiding source reads does not mean that a no-op invocation performs
no filesystem IO. Declaration/dependency validation uses cached metadata each run.

## Proof

`python3 tests/portability/traits.py` covers warm reuse, same-sized edits with restored
mtime, shared-consumer invalidation, added/removed nested directories, missing traits,
duplicate names/methods, adaptations, nested trait use, namespace restrictions,
source diagnostics, modified cache rejection, and global/same-file traits.

For native parity:

```bash
python3 tests/portability/traits.py \
  --target-checkout /tmp/scpp-v0.1.76-probe \
  --results FRESH_RESULTS_DIRECTORY
```

[Recorded evidence](../planning/compiler_migration/results/traits-01/summary.json)
includes a shared trait with a private helper that mutates each consumer's `$this`.
PHP and strict native execution produce `3:13:3`. Settled no-op cache counters are
zero for directory listings, source reads, tokenization and token loads. Generated
fixtures and the runner are retained alongside command logs. Existing foundation,
prologue, native-framework and cumulative compiler proofs are also run for this change.

[Final cache validation](../planning/compiler_migration/results/traits-01/final-validation.json)
includes malformed-index rejection added during consolidation; its generated
fixture bytes match the native-tested output. The
[cumulative compiler proof](../planning/compiler_migration/results/traits-cumulative-01/summary.json)
records strict native execution and all sixteen retained compiler fixtures.
