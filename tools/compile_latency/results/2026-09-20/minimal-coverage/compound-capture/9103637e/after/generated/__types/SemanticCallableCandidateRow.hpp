#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SemanticCallableCandidateRow {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> candidate_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> helper_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> callable_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> namespace_policy_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> generator_allowed_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> source_consumption_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	string_t helper_key = string_t("");
	string_t core_namespace = string_t("");
	string_t php_namespace = string_t("");
	string_t authority_source = string_t("");
	string_t source_consumption_note = string_t("");
};
}
