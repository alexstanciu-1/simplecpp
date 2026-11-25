#pragma once
#include "lang/php/php_exceptions.hpp"
#include "scpp/bool_t.hpp"
namespace exception_probe {
inline scpp::bool_t same(const scpp::shared_p<scpp::Exception>& left,
                        const scpp::shared_p<scpp::Exception>& right) {
    return scpp::bool_t(left.native_value().get() == right.native_value().get());
}
}
