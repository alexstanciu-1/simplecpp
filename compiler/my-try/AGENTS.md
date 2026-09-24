# Small compiler intake and review

This folder is shared with the user's IDE. Reread current files immediately
before editing. Preserve unrelated changes; do not refresh imported files by
overwriting local work with upstream copies.

Read [MODEL.md](MODEL.md) before changing retained data. `compile/model.php` is the root
of the compiler object graph; processing stays in its existing owners. Document
collection bounds, references and index maintenance alongside representation
changes. Keep existing PHP storage until a change is agreed; this model does not
require a database, a generic ORM framework, stored IDs or immediate weakrefs.
The agreed Model root uses static fields accessed as `Model::$field`; compiler
instances share this data. Start a new compilation through `Compiler::init()`,
which resets the model. Static-property conversion and required-field declarations are proved in focused
fixtures; Storage template bindings remain a follow-up. Review native initialization
checks at each worker boundary.

Retained model data must not reference workers. Workers consume and return data
records. Use Storage for nested owned record lists as well as root collections;
keep scalar lists and dedicated indexes typed and document their owners. Preserve
shared identity and local positions. Do not turn index references into new owners.

Read [Storage](helpers/STORAGE.md) when reviewing
record layout or conversion. PHP object handles do not prescribe native pointers:
classify value storage, ownership and reference lifetime explicitly. Native bulk
processing and memory efficiency are goals; preserve required behavior while
reviewing incidental PHP aliasing. Start with shared object handles; no views or
readonly-membership layer are currently required.

Document model ownership using [ownership annotations](docs/ownership.md): mark
owning stores, AST child ownership and direct-reference provenance. Mark convenience
backlinks and non-owning indexes @reference.weak. These are design annotations for
the Simple C++ port, not implemented weak-reference lowering. Do not invent a store
for directly owned records such as scopes; document their actual owners.

## Default procedure for each imported slice

1. The initial full import from
   `/home/alexv/__AI/scpp_compiler_3/prototype/my-try/` is authorized. Record
   origins and preserve the running PHP pipeline. Future additions follow these
   same intake rules; review conversion and optimization file by file together.
2. Separate data records into the owning process's `structures.php`. Keep
   processing in clearly named files/classes; do not create a global data bucket.
3. Use `namespace scpp\compiler;` throughout the project. Numbered directories
   are organization, not namespace components. Qualify PHP exception classes and
   external constants so namespace changes preserve behavior.
   Prefer short names for types in this namespace, including conversion
   annotations: use `vector<module>` and `vector<file>`, not fully qualified
   project names. Apply this to newly imported and edited code. Qualify types
   only when needed to resolve an actual ambiguity or refer outside the namespace.
4. Use lowercase `snake_case` for data records and `Capitalized_Snake_Case` for
   processing/stateful classes. Keep record identity/copy behavior unchanged
   unless separately agreed; moving a PHP class does not make it a native struct.
5. On initial import and whenever adding code, establish conversion data types
   wherever needed: local variables, properties, arguments/parameters, returns,
   constants and container elements/keys. Use PHP declarations where sufficient;
   add supported adjacent portability annotations only where they supply missing
   native type information, such as `vector<T>`, `hash<V, K>` or an agreed
   fixed-width integer type. Clear PHP signatures such as
   `function init(file $file, string $path): void` need no extra annotations.
   The same applies to typed properties and unambiguous local expressions such
   as `new file()` or string concatenation. Foreach elements inherit their
   declared container element type; do not repeat it without a concrete need.
   Keep explicit annotations at stabilization boundaries and where empty arrays
   lack element/key information. Do not invent annotation syntax for unsupported
   declaration sites; identify the converter gap for review.
   For unadapted host API results, document the truthful
   PHP union/shape with `@var`, check failure, then stabilize the successful value.
   Documentary annotations alone do not establish converter support.
   For Storage, write explicit element intent: `public Storage $tokens /** Storage<token> */;`.
   Storage is a numeric object list; Keyed_Storage<T> is the separate string-keyed
   collection. Both inherit Storage_Abstract for common behavior. Native records
   use shared_p<T>; capacity is an
   optional constructor argument. No key-mode or readonly template arguments remain.
   Custom Storage conversion remains deferred; see helpers/STORAGE.md. AST nodes
   own payloads and nested Storage child lists; do not recreate parallel node/payload
   registries or Storage_View infrastructure without a concrete approved need.

6. Follow compiler 3's formatting, purpose comments and method-order conventions
   (source links in README.md). Preserve behavior during this preparation;
   describe unresolved dependencies and conversion gaps explicitly.
7. Run PHP lint and a focused behavior check appropriate to structural changes.
   Report what is proved; do not claim native conversion from PHP-only checks.
8. After import preparation and PHP behavior verification, review files together
   before further conversion or optimization. Do not claim that imported PHP is
   fully convertible. Update this procedure as decisions evolve.

Use the portable-PHP skill and existing converter/framework for adaptation.
The parked compiler's stage migration sequence does not govern this intake.
Do not add incremental machinery, new language behavior, or speculative native
storage adaptations as part of these default preparation steps.


Required fields stay nonnullable even without written initializers: populate them
before read or publication. Nullable is explicit (`?T`) only where absence is a
valid usable state; weak-reference intent does not imply nullable. Track the
remaining audit in REVIEW.md#explicit-nullability-audit. Native empty initialization
is not permission to read or publish an incomplete required field.
