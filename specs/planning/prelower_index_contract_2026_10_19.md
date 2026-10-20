# Concrete indexing: runtime interoperability prerequisite
Doc Status: planning

Date: 2026-10-19. Status: approved and implemented in the bounded integer/vector/hash indexing slice.
Baseline of the original audit: `f34ea284`. The diagnostic below records the pre-fix failure.
Current implementation: [runtime contract](../../runtime/specs/concrete_type_interoperability.md);
validation: [indexing evidence](results/prelower_indexing_2026_10_19/README.md).

## Reproduced gap

The prepared declaration `function demand(hash<int,int> &$items): void {}` is valid
in both modes. Ordinary generated C++ builds and runs. Concrete substitution fails
its native layout probe: `hash_t<ConcreteInt, ConcreteInt>` fails the runtime's
`typed_hash_key` assertion because `key_ops<ConcreteInt>` is unavailable.
See [Clang diagnostic](results/prelower_index_contract_2026_10_19/concrete-build.stderr).

The focused shared-runner probe passed its expected outcomes in 6.59s, using CLI
fallback. Local evidence/scripts: `/tmp/prelower-index-contract/`. This is a reproduced
blocker, not successful indexing, runtime parity, or a native compiler rebuild.

Source types and inference need no change. The gap is the physical runtime contract.

## Existing owners and constraints

- `CPP_Types::implementation_representation` recursively substitutes runtime template
  arguments. `CPP_Opaque_Representation` generates concrete lifecycle and integer
  scalar access, but not native trait interoperability.
- `runtime/include/scpp/support/hash_t.hpp`: `key_ops`, `typed_hash_key`, and
  `next_append_key` own hashing, equality and integer append semantics. Append tests
  `detail::is_int_t_v<T_KEY>`; adding only hash/equality support would leave integer
  append taking the non-integer failure path.
- `runtime/include/scpp/detail.hpp`: `is_int_t` and `int_rep` recognize native integer
  templates. Concrete integer width/sign already exist in the compiler's type model.
- Runtime indexing/path helpers accept native container types and, for several
  operations, exact native key types. Concrete argument roles must preserve these
  contracts rather than assume every argument can be unwrapped identically.
- `CPP_Index_Write` already evaluates keys and RHS before resolving interior places;
  `CPP_Keyed_Path` already lazily evaluates keys and re-resolves prefixes. These
  algorithms must remain the sequencing owners.

## Agreed direction

Establish a documented runtime interoperability contract for concrete representations,
with backend binding information attached to the existing type/binding owners.
Publish support-only adapters before native template/layout instantiation. They
should expose native access and the required key/integer traits, delegating hash,
equality, integer range and append behavior to existing runtime implementations.
Keep concrete container elements and keys in their intended physical storage; do not
silently revert selected template arguments to native types.

Then generate demanded concrete indexing/path interfaces and delegate them to runtime
operations. Reuse the ordinary read/write/removal emitters and prepared facts. Runtime
family overloads remain runtime-owned; the engine only manages demand and artifacts.
Reference-only operations must preserve actual element identity and introduce no copy.

This touches backend representation/binding publication and runtime trait/helper
contracts. It does not need a new AST, semantic inference pass, or parallel registry.
The smaller alternative is backend-generated specializations of today's private
runtime traits, but that couples generated support directly to implementation details
and still needs a defined policy for native versus concrete operation arguments.

Validation must cover vector and hash, integer-key append including overflow and
negative-key behavior, supported removal, nested prefix changes during key/RHS
execution, missing-prefix short-circuiting, reference identity, copy-failure cleanup,
O0/O2/sanitizer allocation/copy parity, and incremental demand/layout replay. Existing
native behavior is the comparison baseline. No parity claim is established yet.

The user approved this runtime/backend extension after the audit. The bounded
implementation uses an explicit integer representation alias and existing runtime
traits, plus demanded operation overloads; broader interoperability remains open.
