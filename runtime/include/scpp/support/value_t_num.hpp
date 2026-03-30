#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"

#include <cstdint>
#include <stdexcept>

namespace scpp {

// Polymorphic numeric value — holds exactly one of: bool_t, int_t, float_t.
//
// Notes:
// - kind_t is the only discriminator needed — no heap allocation, no pointer members
// - sizeof(value_t_num) == 16 bytes (1 byte kind + 7 pad + 8 byte union)
// - null_t / nullopt_t / nullptr_t all construct as bool_t{false} — sentinels are
//   not stored; use nullable<value_t_num> if absence must be represented
// - no any_v, no string_v, no table_v — use value_t for those
class value_t_num final {
public:
    enum class kind_t : std::uint8_t {
        bool_v  = 0,
        int_v   = 1,
        float_v = 2,
    };

private:
    kind_t type_;

    union {
        bool     bool_value_;
        int64_t  int_value_;
        double   float_value_;
    };

public:
    // Zero state is bool_t{false} — deterministic and cheap.
    constexpr value_t_num() noexcept
        : type_(kind_t::bool_v), bool_value_(false) {}

    // Sentinel constructors — map to bool false (absence belongs in nullable<>).
    constexpr value_t_num(null_t)    noexcept : type_(kind_t::bool_v), bool_value_(false) {}
    constexpr value_t_num(nullopt_t) noexcept : type_(kind_t::bool_v), bool_value_(false) {}
    constexpr value_t_num(nullptr_t) noexcept : type_(kind_t::bool_v), bool_value_(false) {}

    // scpp type constructors.
    constexpr value_t_num(const bool_t  &v) noexcept : type_(kind_t::bool_v),  bool_value_(v.native_value()) {}
    constexpr value_t_num(const int_t   &v) noexcept : type_(kind_t::int_v),   int_value_(v.native_value())  {}
    constexpr value_t_num(const float_t &v) noexcept : type_(kind_t::float_v), float_value_(v.native_value()) {}

    // Native convenience constructors.
    constexpr value_t_num(bool    v) noexcept : type_(kind_t::bool_v),  bool_value_(v)  {}
    constexpr value_t_num(int64_t v) noexcept : type_(kind_t::int_v),   int_value_(v)   {}
    constexpr value_t_num(double  v) noexcept : type_(kind_t::float_v), float_value_(v) {}

    // int_t implicitly widens to float_t — mirrors the configured promotion rule.
    constexpr value_t_num(float_t v, int_t src) noexcept = delete; // use float_t ctor explicitly

    // Trivial — no heap members.
    ~value_t_num() = default;
    value_t_num(const value_t_num &)            = default;
    value_t_num(value_t_num &&)                 = default;
    value_t_num &operator=(const value_t_num &) = default;
    value_t_num &operator=(value_t_num &&)      = default;

    // Assignments from scpp types.
    constexpr value_t_num &operator=(const bool_t  &v) noexcept { type_ = kind_t::bool_v;  bool_value_  = v.native_value(); return *this; }
    constexpr value_t_num &operator=(const int_t   &v) noexcept { type_ = kind_t::int_v;   int_value_   = v.native_value(); return *this; }
    constexpr value_t_num &operator=(const float_t &v) noexcept { type_ = kind_t::float_v; float_value_ = v.native_value(); return *this; }
    constexpr value_t_num &operator=(null_t)    noexcept { type_ = kind_t::bool_v; bool_value_ = false; return *this; }
    constexpr value_t_num &operator=(nullopt_t) noexcept { type_ = kind_t::bool_v; bool_value_ = false; return *this; }
    constexpr value_t_num &operator=(nullptr_t) noexcept { type_ = kind_t::bool_v; bool_value_ = false; return *this; }

    // Inspection.
    [[nodiscard]] constexpr kind_t kind()    const noexcept { return type_; }
    [[nodiscard]] constexpr bool_t is_bool() const noexcept { return bool_t{type_ == kind_t::bool_v};  }
    [[nodiscard]] constexpr bool_t is_int()  const noexcept { return bool_t{type_ == kind_t::int_v};   }
    [[nodiscard]] constexpr bool_t is_float()const noexcept { return bool_t{type_ == kind_t::float_v}; }

    // Typed accessors — throw on kind mismatch.
    [[nodiscard]] bool_t  bool_value()  const {
        if (type_ != kind_t::bool_v)  throw std::logic_error("value_t_num: not bool");
        return bool_t{bool_value_};
    }
    [[nodiscard]] int_t   int_value()   const {
        if (type_ != kind_t::int_v)   throw std::logic_error("value_t_num: not int");
        return int_t{int_value_};
    }
    [[nodiscard]] float_t float_value() const {
        if (type_ != kind_t::float_v) throw std::logic_error("value_t_num: not float");
        return float_t{float_value_};
    }

    // clone() is trivial for a value type — same as copy.
    [[nodiscard]] constexpr value_t_num clone() const noexcept { return *this; }
};

static_assert(sizeof(value_t_num) == 16,
    "value_t_num must be 16 bytes: 1 kind + 7 pad + 8 union");

} // namespace scpp
