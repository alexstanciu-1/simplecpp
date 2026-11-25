#include "boxed_conversion.hpp"
#include <stdexcept>
namespace scpp::detail {
namespace {
template<class To, class From> To scalar_copy(const From& value) {
    using kind = typename From::kind;
    switch (value.type()) {
        case kind::null: return To{};
        case kind::boolean: return To::boolean(value.get_boolean());
        case kind::integer: return To::integer(value.get_integer());
        case kind::floating: return To::floating(value.get_floating());
        case kind::string: return To::string(value.get_string());
        case kind::container: throw std::runtime_error("boxed_container_family_conversion");
    }
    throw std::logic_error("boxed_invalid_kind");
}
}
dynamic_value dynamic_from_mixed_scalar(const mixed_value& value) { return scalar_copy<dynamic_value>(value); }
mixed_value mixed_from_dynamic_scalar(const dynamic_value& value) { return scalar_copy<mixed_value>(value); }
}
