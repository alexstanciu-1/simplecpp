#pragma once
#include "scpp/detail/concrete_integer_keys.hpp"
#include "scpp/detail/string_access.hpp"

namespace scpp::hash_detail
{
    // Keep concrete string keys on the existing byte hashing/equality authority.
    template<>
    struct key_ops<scpp::string> final
    {
        static std::uint64_t hash(const scpp::string &key)
        {
            return key_ops<string_t>::hash(detail::string_access::get(key));
        }
        static bool equal(const scpp::string &left, const scpp::string &right)
        {
            return key_ops<string_t>::equal(detail::string_access::get(left), detail::string_access::get(right));
        }
    };
}
