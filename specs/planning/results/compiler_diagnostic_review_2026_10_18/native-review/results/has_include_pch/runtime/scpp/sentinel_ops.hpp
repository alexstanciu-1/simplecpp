#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"

namespace scpp {

// Configured equality surface for sentinel values.
//
// Enforces:
// - null, nullopt, and null_ptr can be compared explicitly
// - the configured semantics currently treat all three sentinels as equivalent
[[nodiscard]] constexpr bool_t operator==(null_t, null_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator!=(null_t, null_t) noexcept { return bool_t(false); }
[[nodiscard]] constexpr bool_t operator==(nullopt_t, nullopt_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator!=(nullopt_t, nullopt_t) noexcept { return bool_t(false); }
[[nodiscard]] constexpr bool_t operator==(nullptr_t, nullptr_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator!=(nullptr_t, nullptr_t) noexcept { return bool_t(false); }
[[nodiscard]] constexpr bool_t operator==(null_t, nullopt_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator==(nullopt_t, null_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator!=(null_t, nullopt_t) noexcept { return bool_t(false); }
[[nodiscard]] constexpr bool_t operator!=(nullopt_t, null_t) noexcept { return bool_t(false); }
[[nodiscard]] constexpr bool_t operator==(null_t, nullptr_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator==(nullptr_t, null_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator!=(null_t, nullptr_t) noexcept { return bool_t(false); }
[[nodiscard]] constexpr bool_t operator!=(nullptr_t, null_t) noexcept { return bool_t(false); }
[[nodiscard]] constexpr bool_t operator==(nullopt_t, nullptr_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator==(nullptr_t, nullopt_t) noexcept { return bool_t(true); }
[[nodiscard]] constexpr bool_t operator!=(nullopt_t, nullptr_t) noexcept { return bool_t(false); }
[[nodiscard]] constexpr bool_t operator!=(nullptr_t, nullopt_t) noexcept { return bool_t(false); }

} // namespace scpp
