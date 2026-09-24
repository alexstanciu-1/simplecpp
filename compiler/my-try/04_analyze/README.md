# Analysis
Doc Status: supporting

1. `collect/` records occurrences during parsing and publishes each successful
   file's declarations. It does not walk the AST afterward or reject duplicate names.
2. `prepare.php` consumes the declaration/reference index lists for the first LLVM
   experiment. It supplies variable targets for unambiguous same-file names, and direct function
   targets through lexical function pools, including definitions in another file.

The coordinator calls name preparation after all files have parsed. It is a bounded
preparation owner, not a complete language resolver or STAN implementation. General
lookup rules and checks will be added here separately from LLVM emission.
