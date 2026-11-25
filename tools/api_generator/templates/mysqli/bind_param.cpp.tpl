#pragma once
#include "scpp/mysqli/mysqli_stmt.hpp"
#include "scpp/string/type.hpp"
namespace scpp { class dynamic; class mixed; }
namespace scpp::detail {
struct db_slot {
    enum class kind { integer, real, bytes, boxed };
    kind type;
    const void* address;
    db_slot(const int64& v):type(kind::integer),address(&v){}
    db_slot(const float64& v):type(kind::real),address(&v){}
    db_slot(const string& v):type(kind::bytes),address(&v){}
    db_slot(const mixed& v):type(kind::boxed),address(&v){}
    bool accepts(char code) const;
    dynamic read() const;
};
}
namespace scpp::mysqli_api {
bool_t bind_slots(const mysqli_stmt&, const string&, const detail::db_slot*, __SIZE_TYPE__);
template<class... T>
bool_t bind_param(const mysqli_stmt& stmt, const string& types, T&... values) {
    const detail::db_slot slots[]{detail::db_slot{values}...};
    return bind_slots(stmt, types, slots, sizeof...(values));
}
}
