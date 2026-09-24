# Shared object collections
Doc Status: supporting

Storage<T> owns a numeric list of object handles. PHP objects are shared naturally;
the intended native element is shared_p<T>. Assigning the container aliases it;
constructing another Storage creates a separate list. Appending an object stores
the same object, not a copy. Repeating an object in a list is allowed.

## API

- new Storage(capacity = 0): create an empty list; capacity is a native hint.
- append(record): add an object and return its numeric position; [] append is equivalent.
- [position]: get the shared object; a missing position throws without inserting.
- replace(position, record), or [position] = record: replace an existing member.
- remove(position): require and remove a member. unset([position]) permits absence.
- isset([position]), count(), is_empty(), foreach: normal collection access.
- reserve(capacity): nonnegative native hint; no allocation or membership change in PHP.

Positions are assigned monotonically from zero. Deletion leaves holes; neither
replacement nor deletion renumbers other members. Count means live members, not
maximum position. Negative positions are absent; wrong offset types reject rather
than applying PHP key coercion. Appends reject position exhaustion at PHP_INT_MAX.
Records must be objects, not scalar values or null. Iterator mutation is outside
the contract; callers must not modify membership while traversing it.

Replacing/removing membership does not revoke previously retrieved object handles.
Editing a record field is visible through all handles to that object. A stored
record does not acquire a hidden owner, ID, readonly flag or serialization policy.

Storage has no string-key mode. Neither collection needs a position/key map,
secondary-index hooks, reentrancy guard, failed-owner state, Storage_View hierarchy
or internal_* operations.
Current compiler name indexes are separate typed arrays maintained by their worker.
Do not restore removed machinery without an actual use case.

## Conversion boundary

Write explicit element intent as `public Storage $tokens /** Storage<token> */;`.
Construction capacity is an argument, never a template policy. The PHP helper is
not itself the intended native implementation. Generic binding remains pending.
Future native specialization may improve allocation/layout/serialization while
preserving observable identity and mutation behavior, or explicitly revising that
contract when needed. No low-level layout work is required for this PHP cleanup.


## Shared base and string-keyed collection

Storage_Abstract owns the backing PHP object array and common validation, access,
replace/remove, iteration, count/is_empty and reserve behavior. Storage adds numeric
append/position rules. Keyed_Storage adds string-key insertion rules. Both inherit
the capacity-only constructor. The abstract base is not directly instantiated;
concrete source types are Storage<T> and Keyed_Storage<T>.

Keyed_Storage supports add(string_key, record) for unique insertion, [key] = record
for insertion or replacement, and the common operations by string key. It has no
numeric-position API and no keyless append. Integer offsets reject rather than
coercing to strings. Empty and numeric-looking string keys are valid; embedded NUL
bytes also remain distinct. PHP prefixes internal keys to avoid numeric-string
array coercion; foreach returns the original strings. Native code need not use
that encoding. Materializing a PHP array from iteration can apply PHP array key
coercion again; that is outside the collection's iteration contract.

Iteration follows insertion order. Replacement keeps order; removal and reinsertion
move a key to the end. Duplicate add rejects without replacing. Existing object
handles survive replacement/removal exactly as in numeric Storage. Compiler usage includes named struct types/fields, external targets and the
instance registry. Scope overload pools and sparse integer indexes remain typed
arrays; collection membership does not duplicate record identity or redefine
semantic ownership. See [MODEL.md](../MODEL.md#collection-choices-during-llvm-preparation) for the collection inventory.

Host boot loads storage_abstract.php before storage.php and keyed_storage.php.
Generic bindings and native class structure remain pending; PHP inheritance is
code reuse, not proof of converter support for arbitrary inheritance.
