#pragma once

#include <cstdint>
#include <memory>
#include <optional>
#include <stdexcept>
#include <string>
#include <string_view>
#include <utility>
#include <vector>

namespace scpp {

// Forward declarations for cross-type references.
//
// Purpose:
// - keeps header dependencies smaller
// - allows wrappers to reference each other without forcing full definitions everywhere
class null_t;
class nullopt_t;
class nullptr_t;
class bool_t;
class int_t;
class float_t;
class string_t;

template <typename T> class vector_t;
template <typename T> class shared_p;
template <typename T> class unique_p;
template <typename T> class weak_p;
template <typename T> class nullable;

// Cast helper forward declaration.
template <typename To, typename From>
To cast(const From &value);

namespace detail {

// Common utility alias used by generated code for template normalization.
template <typename T>
using remove_cvref_t = std::remove_cv_t<std::remove_reference_t<T>>;

// Helper used in dependent static_asserts so unsupported templates fail cleanly.
template <typename T>
constexpr bool always_false_v = false;

} // namespace detail

} // namespace scpp
