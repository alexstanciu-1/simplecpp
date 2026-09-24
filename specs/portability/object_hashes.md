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

The property becomes a native hash. The construction becomes `[]` at the explicitly
typed local boundary. A SplObjectStorage return has an adjacent hash annotation as
well. Bare or incorrectly annotated constructors/properties reject. The native
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
  Current compiler consumers use only keyed operations.
- Do not use null keys/values, SplObjectStorage-specific methods, weak keys, or
  assignment that relies on shared map membership. Those require a separate contract.
- PHP arrays cannot carry object keys. Keep object-keyed maps in the explicit
  SplObjectStorage carrier; ordinary arrays remain scalar-keyed.

Current maps: Template_Checker.files maps collected_file to llvm_prepared_file;
LLVM_Preparation.owners maps collected_name to llvm_prepared_file, file_indexes maps
llvm_prepared_file to int, and structs maps collected_name to llvm_struct_type.
LLVM_Struct_Preparation constructs the last map before handing it to preparation.
These are transient indexes, with no retained-model layout change.

`php tests/portability/object_hashes.php` proves PHP identity/replacement/removal,
real converter boundaries, rejections, and the native TypeMapper result
`hash_t<shared_p<Row>, shared_p<Key>>`. PHP compiler model and LLVM tests also pass.
No native compilation or native runtime execution was performed in this slice.
