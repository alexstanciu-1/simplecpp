# Typed-storage body integration
Doc Status: supporting

Real application workers and joins prepare concrete storage types/functions before
signature, local-type and body checking. Twenty-three PHP/native cases cover scalar
and record elements, nested indices/fields, const reads and rejected writes, record
borrows, all six storage roles, exact retained storage effects and call-before-RHS
ordering. A target index is consumed with its own call boundary before the RHS;
Expression_Order remains the shared value/call traversal owner.

The first fixture attempted mutable source reference parameters for noncopyable
storage owners. Parameter_Contracts correctly rejects this in both migrated and
preserved implementations. Tests now use local owners for mutation and const source
parameters for observation. No production restriction was changed. Checked storage
plans do not establish initialized allocation state, bounds safety or legal resource
lifetimes: allocation analysis must still reject unsafe complete programs. Physical
storage primitives are outside this body-checking fixture and are not executed.

Remaining body work includes managed lifecycle construction/assignment/return plans,
complete debug export and session coordination. Lifetime analysis remains unmigrated.

Run `python3 compiler/tests/body_storage/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/body-storage-01`.
