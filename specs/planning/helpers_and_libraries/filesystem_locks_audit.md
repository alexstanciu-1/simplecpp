# Filesystem lock family

Doc Status: planning

Compared the v0.1 release at `d2f63e37e`, my-try at `624ab8a2d` and active v0.2 at `6d0ca8074`. Recommend taking the four general names together in the existing filesystem module, using its usual module selection and general-API visibility. No PHP stream/flock adapter is proposed.

## Exposure and source signatures

The release strict map exposes try, release and transfer. Its caller declares a handle slot and passes it as the output. The legacy map has no lock entries. My-try registers all three plus an explicit empty factory. Its filesystem catalog declares a shared class usable as a result payload. The native provider diff between the pinned revisions adds the factory and reuses it for candidate and destination allocation. The archived lock specification includes this later factory. [Profile crosswalk](intake.tsv) · [my-try filesystem catalog](../../../v0.1/runtime/metadata/modules/filesystem.json) · [Historical contract](../../../v0.1/specs/builtins/filesystem/file_locks.md)

| Proposed v0.2 signature | State transition |
| --- | --- |
| `fs_lock_empty(): file_lock_handle` | Creates a distinct present token with no descriptor. No filesystem access. Works on every target. |
| `fs_lock_try(file_lock_handle &$out, string path, bool shared = false): result<bool>` | Requires a writable empty/released handle slot. Success(true) installs a fresh acquired token in that slot only. Success(false) means another acquisition conflicts. Invalid input, an already-held output and other OS failures return an error. Both contention and error preserve the output. |
| `fs_lock_release(file_lock_handle h): result<bool>` | Unlocks and closes once. Every alias of the same token observes release. Empty/already released succeeds on Linux. Unlock failure retains ownership. After successful unlock, a late close error is reported with the token already released. |
| `fs_lock_transfer(file_lock_handle h): result<file_lock_handle>` | Allocate the destination token first, then move the live descriptor into it. Acquisition persists continuously. Every old alias becomes released. Empty, released and inherited live tokens give an error. Allocation failure leaves the original owner intact. |

## Which alias owns the lock?

1. Create an empty token A, then copy the handle. Acquire through the original slot. That slot now refers to a fresh held token B. The copy still refers to empty A.
2. Copy B. Both handles now share one acquisition. Releasing through either handle releases it for both. Dropping just one of the two handles keeps the lock held.
3. Alternatively, transfer B to C. Both old handles still refer to B, now released. Releasing B succeeds harmlessly. Transferring B again fails. A separate process still contends until C is released or its last owner is destroyed. The returned result can itself retain C under ordinary carrier ownership.
4. A released slot can acquire again. Only that slot is rebound, so old aliases do not gain the new acquisition. Distinguish a checked bool's successful payload from whether the carrier contains a value: a contended attempt has a value and that value is false.

## Provider and lowering boundary

Use the v0.2 opaque handle pattern already exercised by streams and managed processes. The wrapper copies shared ownership. The private state owns the descriptor and acquiring process identity. Try alone takes a writable wrapper reference. Release and transfer can borrow the wrapper as const in native C++ and mutate the shared state. Ordinary source assignment and native wrapper moves retain their existing meanings. Application code must serialize operations on the same token; reference counting protects lifetime only. [Active resource contracts](../../stream_process_curl_modules.md)

The existing filesystem publication has no lock handle or checked handle result. Its function generator supports writable reference modes, while the resource generator publishes opaque handles and checked handle carriers for a separate set of modules and normally borrows handle operands. Integrating the two capabilities under runtime-filesystem is the required generator work. Mark argument zero of try as mutable in metadata, require an actual writable slot and test both arities. Keep the handle and result declarations narrow and OS-free. Store Linux headers and calls in the compiled provider. [Filesystem function preparation](../../../tools/api_generator/php/module_functions.php) · [Resource publication](../../../tools/api_generator/php/resource_modules.php)

## OS contract and verification

Use nonblocking Linux advisory file locks on a stable regular file. Shared readers coexist and require an existing readable file. Exclusive access conflicts with either mode and may create the file without truncation. Empty/NUL paths, missing parents and invalid file types are errors. Release never unlinks the file. Retry interrupted open/lock operations; never retry a numeric descriptor after close. Use close-on-exec. On fork, a child's inherited token must reject public release/transfer and its destructor must only close its descriptor. Explicit unlock by the parent releases the lock even while a child retains an inherited descriptor. On other targets recommend the donor's explicit unsupported error for try, release and transfer. [Native provider](../../../v0.1/runtime/include/modules/filesystem/file_lock.cpp) · [Linux flock semantics](https://man7.org/linux/man-pages/man2/flock.2.html)

During this audit the archived native fixture compiled with Clang C++23 and passed locally. It exercised independent processes and PHP flock, reader/writer contention, alias invalidation, fork/exec cleanup, stable inode and contents, and descriptor counts after repeated failure/contended paths. This is donor evidence. For v0.2 acceptance, repeat from a generated caller with a competing process; distinguish empty-token aliases taken before try from held-token aliases taken afterward, check contention versus errors, and show the contender stays blocked after release of a transferred-from alias. Also test disabled module/general-name visibility and reject a temporary or readonly output. [Native fixture](../../../v0.1/tests/runtime/native/test_file_locks.cpp) · [Historical source caller](../../../v0.1/tests/tools/fixtures/file_locks/main.phs)

## Stage 1 outcome

The filesystem publication now supplies all four operations, the opaque handle and its checked result. The generated-caller proof held an exclusive lock, transferred it, released an old alias, then kept a separate reader and writer contending until explicit release. A native fixture exercised last-owner cleanup, repeated attempts, fork inheritance and close-on-exec. Injected unlock and late close errors verified retry ownership and descriptor invalidation. The unsupported provider branch was compiled and run on Linux with its Linux-specific code disabled. [Active contract](../../filesystem_module.md#advisory-file-locks) · [Source proof](../../../compiler/tests/filesystem_locks_native.py) · [Descriptor lifecycle proof](../../../tests/filesystem_lock_lifetime.cpp)
