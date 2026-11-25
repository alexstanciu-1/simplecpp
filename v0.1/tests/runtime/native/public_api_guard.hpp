#pragma once

// Validate the real CMake consumer include path, not a hand-written probe command.
#if __has_include("scpp/runtime.hpp") || __has_include("scpp/int_t.hpp") || __has_include("scpp/runtime_error.hpp") || __has_include("modules/tasks/tasks.hpp")
#error "The public API target exposed an implementation include root"
#endif
