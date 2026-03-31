#include "scpp/runtime.hpp"

// The generated runtime is not fully header-only anymore.
// Keep this translation unit as the single place that pulls in
// runtime implementation files required by generated snippets.

#include "../include/scpp/support/value_t.cpp"
#include "../include/scpp/support/table_t.cpp"
#include "../include/scpp/support/var_dump.cpp"
