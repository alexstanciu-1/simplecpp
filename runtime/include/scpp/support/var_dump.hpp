#pragma once
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include <iostream>

namespace scpp::php {

void var_dump(const mixed_t& v);
void var_dump(const hash_t<mixed_t>& t);

inline void var_dump(null_t v) { var_dump(mixed_t{v}); }
inline void var_dump(nullopt_t v) { var_dump(mixed_t{v}); }
inline void var_dump(nullptr_t v) { var_dump(mixed_t{v}); }
inline void var_dump(const bool_t& v) { var_dump(mixed_t{v}); }
inline void var_dump(const int_t& v) { var_dump(mixed_t{v}); }
inline void var_dump(const float_t& v) { var_dump(mixed_t{v}); }
inline void var_dump(const string_t& v) { var_dump(mixed_t{v}); }
inline void var_dump(const char* v) { var_dump(mixed_t{v}); }
inline void var_dump(bool v) { var_dump(mixed_t{v}); }
inline void var_dump(std::int64_t v) { var_dump(mixed_t{v}); }
inline void var_dump(double v) { var_dump(mixed_t{v}); }
inline void var_dump(const shared_p<hash_t<mixed_t>>& v) { var_dump(mixed_t{v}); }
inline void var_dump(const weak_p<hash_t<mixed_t>>& v) { var_dump(mixed_t{v}); }

template <typename First, typename... Rest>
inline void var_dump(const First& first, const Rest&... rest) {
    var_dump(first);
    (var_dump(rest), ...);
}

}
