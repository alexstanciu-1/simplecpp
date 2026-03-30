#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"

#include <cstdint>
#include <memory>
#include <stdexcept>

namespace scpp {

// Forward declaration — value_t_data can hold a table but does not need
// the full table_t definition here (pointer-only storage).
template <typename T_VALUE = class value_t_data>
class table_t;

// Polymorphic data value — holds one of: null, bool_t, int_t, float_t,
//                                        string_t, unique_ptr<table_t<value_t_data>>.
//
// Notes:
// - no shared/weak table ownership — use value_t for those
// - string and table are heap-allocated via unique_ptr (8 bytes in union)
// - sizeof(value_t_data) == 16 bytes (1 byte kind + 7 pad + 8 byte union)
// - null_t / nullopt_t / nullptr_t all map to null_v — explicit storable null
class value_t_data final {
public:
    enum class kind_t : std::uint8_t {
        null_v   = 0,
        bool_v   = 1,
        int_v    = 2,
        float_v  = 3,
        string_v = 4,
        table_v  = 5,   // unique_ptr<table_t<value_t_data>> — owned
    };

private:
    kind_t type_;

    union {
        bool                                           bool_value_;
        int64_t                                        int_value_;
        double                                         float_value_;
        std::unique_ptr<string_t>                      string_value_;
        std::unique_ptr<table_t<value_t_data>>         table_value_;
    };

    void destroy() noexcept;
    void move_construct(value_t_data &&other) noexcept;

public:
    // Zero state: null_v.
    value_t_data() noexcept;

    // Sentinel constructors — all map to null_v.
    value_t_data(null_t)    noexcept;
    value_t_data(nullopt_t) noexcept;
    value_t_data(nullptr_t) noexcept;

    // scpp type constructors.
    value_t_data(const bool_t   &v) noexcept;
    value_t_data(const int_t    &v) noexcept;
    value_t_data(const float_t  &v) noexcept;
    value_t_data(const string_t &v);
    value_t_data(const char     *v);

    // Native convenience constructors.
    value_t_data(bool    v) noexcept;
    value_t_data(int64_t v) noexcept;
    value_t_data(double  v) noexcept;

    // Table ownership constructor — unique only.
    value_t_data(std::unique_ptr<table_t<value_t_data>> v) noexcept;

    // Rule-of-five — manual union requires explicit management.
    ~value_t_data();
    value_t_data(const value_t_data &other);
    value_t_data(value_t_data &&other) noexcept;
    value_t_data &operator=(const value_t_data &other);
    value_t_data &operator=(value_t_data &&other) noexcept;

    // Assignments from scpp types.
    value_t_data &operator=(null_t)             noexcept;
    value_t_data &operator=(nullopt_t)          noexcept;
    value_t_data &operator=(nullptr_t)          noexcept;
    value_t_data &operator=(const bool_t   &v)  noexcept;
    value_t_data &operator=(const int_t    &v)  noexcept;
    value_t_data &operator=(const float_t  &v)  noexcept;
    value_t_data &operator=(const string_t &v);
    value_t_data &operator=(const char     *v);

    // Deep copy — string and table are cloned.
    [[nodiscard]] value_t_data clone() const;

    // Inspection.
    [[nodiscard]] kind_t kind()    const noexcept { return type_; }
    [[nodiscard]] bool_t is_null() const noexcept { return bool_t{type_ == kind_t::null_v}; }

    // Typed accessors — throw on kind mismatch.
    [[nodiscard]] bool_t          bool_value()  const;
    [[nodiscard]] int_t           int_value()   const;
    [[nodiscard]] float_t         float_value() const;
    [[nodiscard]] const string_t *string_if()   const noexcept;
    [[nodiscard]] table_t<value_t_data>       *table_if()       noexcept;
    [[nodiscard]] const table_t<value_t_data> *table_if() const noexcept;
};

static_assert(sizeof(value_t_data) == 16,
    "value_t_data must be 16 bytes: 1 kind + 7 pad + 8 union");

} // namespace scpp
