// Historical snippet bundle: one core implementation plus filesystem support.
// CMake compiles runtime_core.cpp and module sources through separate targets.
#include "runtime_core.cpp"

// PHP-flow tests link against this single runtime object, so wrapper-backed
// filesystem calls need their implementation units pulled in here as well.
#include "../modules/filesystem/filesystem.cpp"
#include "../modules/filesystem/snapshot.cpp"
#include "../modules/filesystem/file_lock.cpp"
