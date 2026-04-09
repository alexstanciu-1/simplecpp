#include "scpp/runtime.hpp"

// The generated runtime is not fully header-only anymore.
// Keep this translation unit as the single place that pulls in
// runtime implementation files required by generated snippets.

#include "../include/scpp/support/hash_t.cpp"
#include "../include/scpp/support/mixed_t.cpp"
#include "../include/scpp/support/var_dump.cpp"
