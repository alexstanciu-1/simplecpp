# Object-identity hashes
Doc Status: supporting

Native `hash_t<Value, Key>` already supports shared pointer keys, comparing and
hashing their pointer identities. The compiler's transient indexes use that
existing container; they do not need another native Storage specialization.

Executable PHP uses SplObjectStorage as the carrier, with explicit conversion types:

```php
private \SplObjectStorage $owners /** hash<llvm_prepared_file, shared<collected_name>> */;
$owners /** hash<llvm_prepared_file, shared<collected_name>> */ = new \SplObjectStorage /** hash<llvm_prepared_file, shared<collected_name>> */();
$this->owners = $owners;
```

The property becomes a native hash. The constructor annotation is preserved as
`new hash<Value, shared<Key>>()`, which lowers directly to a native hash value.
Direct property initialization/reset and return expressions therefore need no
intermediate typed local or destination-type inference. Only empty construction
is supported; constructor arguments are rejected. A SplObjectStorage return or concrete method parameter has an adjacent hash
annotation as well. Method parameters use the same explicit carrier grammar;
interface object-hash parameters remain outside this slice. Bare or incorrectly annotated constructors/properties reject. The native
source has no dependency on the PHP SplObjectStorage class. `shared<Key>` states the
key ownership/type explicitly; the native type mapper supplies shared handles for
ordinary record values too. Scalar values, such as file indexes, remain scalars.

This bounded carrier covers keyed set/read/isset/unset operations and return/
publication into a worker field. Keys are non-null records; values are non-null
scalars or records. Equal-looking but distinct key records remain distinct, and
editing a key's fields does not change its identity. Maps retain their keys.

Important authoring boundaries:

- Native hashes have value membership semantics. PHP SplObjectStorage assignment
  aliases membership. Build then publish, and do not mutate through another alias
  after publication. The three compiler owners follow this discipline. Record
  identity remains shared in either language.
- Do not iterate this PHP carrier as if it were a PHP associative array:
  SplObjectStorage iteration exposes keys differently from native hash iteration.
  For deliberate key iteration, write
  `foreach ($map as $key /** @object-key */) { ... }`. PHP yields its object keys;
  the converter emits a native key/value foreach with an unused generated value
  binding. This is explicit syntax intent, not inferred receiver typing. Do not
  mutate membership while continuing that iteration.
- Do not use null keys/values, SplObjectStorage-specific methods, weak keys, or
  assignment that relies on shared map membership. Those require a separate contract.
- PHP arrays cannot carry object keys. Keep object-keyed maps in the explicit
  SplObjectStorage carrier; ordinary arrays remain scalar-keyed.

The parked LLVM regression stack uses these maps: llvm_legacy_template_check_context.files
maps collected_file to llvm_prepared_file; LLVM_Preparation_Run.owners maps
collected_name to llvm_prepared_file, file_indexes maps llvm_prepared_file to int,
and structs maps collected_name to llvm_struct_type. LLVM_Struct_Preparation
constructs the last map before handing it to that legacy preparation stack.
These are transient indexes, not the active shared semantic-fact model; new semantics
belong to File_Preparation and specialization-attached facts. Their relocation and
renaming do not change retained storage.

`php tests/portability/object_hashes.php` proves PHP identity/replacement/removal,
real converter boundaries, rejections, and the native TypeMapper result
`hash_t<shared_p<Row>, shared_p<Key>>`. PHP compiler model and LLVM tests also pass.
No native compilation or native runtime execution was performed in this slice.

`python3 tests/portability/typed_hash_construction.py --results FRESH` proves PHP/native
parity for direct property construction/reset, typed returns and identity-key
access. The proof bypasses STAN; no broader semantic inference is introduced.
