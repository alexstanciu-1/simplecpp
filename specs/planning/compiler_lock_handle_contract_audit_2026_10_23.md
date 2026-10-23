# Required lock-handle initialization audit
Doc Status: planning

## Finding

The current required-object checker is correctly rejecting the portable native
facade's lock_empty: it returns an unreadable required local. This is a runtime API /
binding migration issue under OWN-01, not a need for compiler out-parameters or a
special default-null exception for file_lock_handle.

Evidence owners:
- tools/php_portability/runtime/file_locks.php: lock_empty creates a real File_Lock
  object whose stream is absent. acquire accepts that object and replaces the output
  slot only on success. release preserves the object while clearing its resource;
  transfer produces a new object and empties the old object's resource.
- tools/php_portability/runtime/file_locks.phs: lock_empty currently returns an
  unassigned file_lock_handle; the remaining facade signatures require objects.
- runtime/include/modules/filesystem/file_lock.cpp: lock_try accepts either a null
  shared pointer or a released token, replacing it only on success. lock_release
  accepts null/released tokens. transfer guarantees a new present token on success.
  Private default construction already creates a descriptor-free object, but there
  is no public factory for that state.
- runtime/metadata/modules/filesystem.json: try's mutable output and release's input
  are required file_lock_handle; transfer's successful payload is required too.
- specs/builtins/filesystem/file_locks.md: explicitly permits empty/released handles,
  disallows source constructors, and describes shared token aliasing and failure
  preserving the output. Its pre-nullability wording does not distinguish absent
  pointer from present resource-free token.
- compiler/my-try/tests/output_bindings.py now exercises all read operations but
  preparation stops at the required lock argument before fs_lock_try. The last
  recorded run is /tmp/result-nullable-output-suite-01; legacy passes.

## Recommended bounded runtime API addition (pending discussion)

Expose fs_lock_empty(): file_lock_handle as a factory for a present, descriptor-free
token. The PHP facade already implements exactly that logical carrier. Native
implementation reuses file_lock's private default initialization through a friend
factory and the existing shared_p allocation pattern. No OS resource is acquired.
Route scpp_portability_lock_empty through it, and initialize authored required output
slots with this factory. Required signatures for try/release/transfer remain truthful:
try succeeds with a new live token; release/transfer leave aliases pointing at the
same now resource-free old token. Contention/errors leave the old slot unchanged.

Register through ordinary filesystem function metadata in both compiler routes;
no compiler function-name exception, no special out mode, no source constructor and
no nullable class default. Existing runtime-call prelowering handles the returned
shared provider, as it does other required factories. Update the filesystem spec to
separate token presence from descriptor ownership. Specify the no-OS factory as
platform-independent; existing lock operations continue reporting unsupported
platform errors. Allocation failures propagate as existing runtime allocations do.

This is an additive public runtime factory and a clarification of the empty-token
contract, so obtain discussion before implementing it. It changes neither the
Simple C++ class contract nor OS locking/cleanup policy. Do not silently change the
existing nullable native-pointer overload behavior or broaden source APIs.

Alternative: make try's output and release's input optional, and have the native
facade return ?file_lock_handle from lock_empty. That accurately describes the old
native representation but requires wider annotation propagation through the PHP
carrier, reservation storage and invariant mutable-reference boundaries. Successful
acquisition does not by itself narrow the output's declared nullable type. Choose
this only if nullable pointer absence is intended to be the public source protocol.

## Validation before closure

- PHP and both native routes: empty construction, release/release again, acquisition,
  contention/failure preserving output, reacquisition after release, transfer and old
  aliases, destructor behavior and unchanged native/PHP process contention proofs.
- Ordinary/prelowered factory transport and required-local initialization; reject an
  uninitialized required output argument rather than inventing an out exception.
- Full output-file suite, portable reservation integration, metadata catalog checks,
  measured PHP-hosted bootstrap and source hashes.

The reservation's constructor-body assignment to a required field is a separate
INIT/source conversion obligation: a token factory alone does not establish direct
field initialization for that class. Do not claim complete bootstrap closure here.
