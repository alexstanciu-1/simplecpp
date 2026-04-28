#include "scpp/runtime.hpp"

// The generated runtime is not fully header-only anymore.
// Keep this translation unit as the single place that pulls in
// runtime implementation files required by generated snippets.

#include "../scpp/util/global_string_pool.cpp"
#include "../scpp/support/hash_t.cpp"
#include "../scpp/support/mixed_t.cpp"
#include "../scpp/support/var_dump.cpp"

// PHP-flow tests link against this single runtime object, so wrapper-backed
// filesystem calls need their implementation units pulled in here as well.
#include "../modules/filesystem/filesystem.cpp"
