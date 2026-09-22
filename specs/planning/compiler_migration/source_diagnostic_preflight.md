# Source diagnostic migration preflight
Doc Status: planning

The lexical worker depends on diagnostics\Source_Error, a RuntimeException subclass
with file ID/path and byte span fields. Catching it as a generic framework base
would lose a required typed contract. Changing all producers/consumers to a different
error model is not part of the current migration slice.

## Verified target blocker

On selected clean `2f0d667f38a35ff02ef77e813f409189cba2d032`, an explicitly global
base on a namespaced derived class is incorrectly forwarded/resolved inside the
derived namespace. A three-file nonexception reproducer fails C++ compilation with
an incomplete base and inaccessible inherited field. Strict STAN remains enabled.
The same failure blocks the namespaced source diagnostic over the global framework
exception base. Filed [Simple C++ #233](https://github.com/alexstanciu-1/simplecpp/issues/233).
Target changes belong on v0.1; adoption needs an immutable candidate and rerun proofs.

## Independent catch-strategy proof

A global diagnostic test class avoids only that namespace bug and proves a possible
replacement for the fixed kind-mask dispatch. Nested single-type catches record a
selected arm and a typed handle. Handler bodies execute after all sibling catch
frames have exited. Native RTTI performs matching, so the converter need not infer
class ancestry or allocate a growing exception-kind registry.

The probe checks fields, inherited message, exact object identity, runtime-base
catching, unmatched subtype fallback, and handler exceptions escaping sibling arms.
It is a hand-authored native control, not an implemented converter capability or a
PHP/native production diagnostic proof. It does not prove returns/breaks/continues,
finally, general inheritance, parent-constructor conversion or uncaught diagnostics.

A future bounded implementation would keep exception declaration/initialization and
catch dispatch in the shared portability exception owner, support direct explicitly
qualified framework-base subclasses, and preserve Source_Error's actual PHP contract.
No compiler-specific exception-name hardcoding or source namespace flattening is
proposed. General PHP inheritance remains out of scope.

Evidence: `results/diagnostic-preflight-01/summary.json`; each subdirectory retains
PHS source, strict project configuration and build output. The 23-file ready set is
unchanged. Other dependency-ready migration work can continue while #233 is open.
