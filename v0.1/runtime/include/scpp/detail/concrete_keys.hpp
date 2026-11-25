#pragma once
#include "scpp/detail/concrete_integer_keys.hpp"
#include "scpp/detail/string_access.hpp"

namespace scpp::hash_detail
{
    // Public key capabilities return concrete scalars; native carriers retain
    // the raw-scalar protocol in hash_t.hpp. Neither adapter admits source types.
    template<class T> requires requires(const T &left, const T &right) {
        typename T::scpp_runtime_hash_key;
        { left.hash_key_hash() } -> std::same_as<scpp::uint64>;
        { left.hash_key_equal(right) } -> std::same_as<scpp::boolean>;
    }
    struct key_ops<T> final
    {
        static std::uint64_t hash(const T &key)
        { return key.hash_key_hash().native_value(); }
        static bool equal(const T &left, const T &right)
        { return left.hash_key_equal(right).native_value(); }
    };

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
