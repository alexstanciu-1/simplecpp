# Second-generation bootstrap
Doc Status: planning

The first-generation executable from b3ef5d2f0 consumed the same converted compiler
snapshot. Full publication succeeded, stdout OK: 11:58.99 wall, 823104 KiB RSS.
The compiler executable is -O0; this is not optimized native performance evidence.

Both outputs contain 6276 C++ source/header files. The differing paths/content reduce
to three generated symbol renames (callable type keys 806 versus 801, and a dependent
runtime call key) plus corresponding helper order. After name substitution, 6275
files are byte-identical; containers.cpp has the same line multiset. This comparison
is diagnostic evidence, not a demand for binary equality or a replacement for running
the second-generation compiler. The second-generation bundle built and linked successfully at -O0: 8:23.74 wall,
2418532 KiB RSS. 244 actions compiled, 6 reused, one layout built and two reused.
The temporary host-only 600-second deadline remains necessary for the large
specialization unit; production native build policy is unchanged.

The second-generation executable compiled the same scalar, shared identity and
string-container programs; their native outputs passed with 42, 9 and a||b|.
First and second generation therefore satisfy the agreed bootstrap milestone.
No binary equality is claimed or required. This is representative correctness
acceptance, not exhaustive compiler correctness or optimized performance proof.

Remaining review work is outside this goal: CH15 debts (including action timeout,
registry audit and temporary initialization policy), native -O0 performance and
whether to retain the provisional fs_lock_empty factory. No new language semantics
or cross-owner redesign was needed for the final payload fixes.

