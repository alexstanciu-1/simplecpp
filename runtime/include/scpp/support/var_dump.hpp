#pragma once
#include "scpp/table_t.hpp"
#include "scpp/value_t.hpp"
#include <iostream>

namespace scpp::php {

void var_dump(const value_t& v);
void var_dump(const table_t<value_t>& t);

inline void var_dump(null_t v) { var_dump(value_t{v}); }
inline void var_dump(nullopt_t v) { var_dump(value_t{v}); }
inline void var_dump(nullptr_t v) { var_dump(value_t{v}); }
inline void var_dump(const bool_t& v) { var_dump(value_t{v}); }
inline void var_dump(const int_t& v) { var_dump(value_t{v}); }
inline void var_dump(const float_t& v) { var_dump(value_t{v}); }
inline void var_dump(const string_t& v) { var_dump(value_t{v}); }
inline void var_dump(const char* v) { var_dump(value_t{v}); }
inline void var_dump(bool v) { var_dump(value_t{v}); }
inline void var_dump(std::int64_t v) { var_dump(value_t{v}); }
inline void var_dump(double v) { var_dump(value_t{v}); }
inline void var_dump(const shared_p<table_t<value_t>>& v) { var_dump(value_t{v}); }
inline void var_dump(const weak_p<table_t<value_t>>& v) { var_dump(value_t{v}); }

template <typename First, typename... Rest>
inline void var_dump(const First& first, const Rest&... rest) {
    var_dump(first);
    (var_dump(rest), ...);
}

}
