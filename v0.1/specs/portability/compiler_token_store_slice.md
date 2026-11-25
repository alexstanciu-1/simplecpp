# Token buffers and indexed storage
Doc Status: supporting

Token_Buffer retains its exact immutable source snapshot and an explicit vector of
token records. Token_Set validates positive unique file IDs into a typed map,
retains insertion order for debug export, and returns a nullable shared buffer for
lookup. Missing lookup is an explicit membership test.

The converter accepts nonpromoted constructor parameters using the existing
explicit constructor type/default grammar. Container defaults remain restricted to
nullable null: native empty-vector defaults lower incorrectly on the selected target.
Token_Set uses null as its explicit empty-baseline input, then extracts into a typed
empty vector. Existing no-argument and vector callers retain their behavior. Concrete nullable
scalar/named method returns are emitted as native nullable types; nullable container
and interface returns remain rejected. Reference/untyped parameters remain errors.
Constructor bodies use the same bounded statement parser as other methods.

`scpp\enum_name` exposes PHP enum case names and maps to the target's enum_name
operation. The converter performs no enum lookup or reflection; native declaration
checking belongs to the target. Token_Kinds owns the name operation beside the enum declaration, with an explicit
local enum-typed parameter. A fully qualified enum parameter also failed the
selected target’s name-helper lookup. The target cannot generate enum_name across this
source-unit boundary, even with a qualified parameter; keeping the operation with
the vocabulary avoids duplicating case strings.
Token_Buffer types in container annotations and nullable returns are fully qualified
to avoid collision with the native runtime token_buffer type. Managed imports include this fixed helper uniformly.

Debug JSON is constructed from its explicit schema using the existing Source_Json
quoting contract. Token text uses byte slicing, independently of managed UTF-8 text
helpers. Token offsets/lengths remain producer-owned valid byte ranges. This preserves
the algorithm's schema without carrying temporary heterogeneous PHP arrays into native
code. The output remains debugging data, not a persisted compiler cache format.

Cumulative PHP/native proofs pass on `2f0d667f38a35ff02ef77e813f409189cba2d032`.
Evidence: `specs/planning/compiler_migration/results/token-store-01/summary.json`.
The harness covers present/missing identity, empty baselines, duplicate/invalid IDs,
exact JSON, multi-byte slicing and malformed UTF-8 rejection. All seventeen retained
compiler fixtures pass, including tokenization, lexical updates and variable tokens.
Twenty-two production files are ready. Token acceptance and the lexical algorithm
still need their own migration; this is not whole-tokenizer native coverage.
