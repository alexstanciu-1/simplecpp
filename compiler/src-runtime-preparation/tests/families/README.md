# Runtime-only family proof
Doc Status: supporting

Run `php src-runtime-preparation/tests/families/run.php [absolute-workspace]`.
Use an empty workspace: first-build assertions intentionally require it. The default
creates a fresh temporary directory under the system temporary directory.

`catalog.json` supplies the configured Simple C++ vector and a two-parameter holder;
`holder.hpp` is a deliberately small native fixture. The holder copies its two scalar
arguments at construction. Both forward and reversed type arguments execute through
generated ABI calls, never directly against the native container. Ordinary, full LTO
and ThinLTO linking share accepted metadata and artifacts.

This suite is separate from the compiler fixture runner. It checks the preparation
boundary, not source-language provider-template consumption or scalar-reference ABI.
The existing specialization suite separately preserves its isolated source-record proof.
