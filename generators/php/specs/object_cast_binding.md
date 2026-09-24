# Portability object casts and literal instanceof
Doc Status: supporting

Literal `instanceof` lowers to `scpp::object_is<T>`. The reserved portability call
`scpp_portability_object_cast(value, Class::class)` lowers to an identity-preserving
`scpp::checked_object_cast<T>`. Dynamic targets are rejected; this binding does not
add whole-program type inference. STAN registers the reserved call; lowering checks
its literal marker and arity. Generated interfaces have virtual destructors so their
shared handles support checked polymorphic narrowing.

Successful casts share the original control block. Null/incompatible casts throw
runtime_error with code invalid_object_cast. PHP-framework catch compatibility for
those failures remains unproved and is not promised here. Nonpolymorphic downcasts
are rejected during native instantiation. A derived-to-interface conversion followed
by nullable wrapping needs an explicit interface boundary in authored source.

`php tests/tools/test_scpp_object_cast_emission.php` checks frontend/lowering output.
Successful native identity and instanceof/null/mismatch predicate traces were tested
on PR #244 revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 with these local changes.
The full portable compiler still has independent native build failures; this is not
a completed compiler-port claim.
