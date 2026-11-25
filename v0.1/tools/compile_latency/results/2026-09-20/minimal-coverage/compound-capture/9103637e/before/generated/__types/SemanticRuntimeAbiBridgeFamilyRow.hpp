#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SemanticRuntimeAbiBridgeFamilyRow {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> family_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> member_count = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> deferred_member_count = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	string_t family_key = string_t("");
	string_t source_type_ref_family = string_t("");
	string_t helper_key = string_t("");
	string_t runtime_symbol = string_t("");
	string_t authority_source = string_t("");
};
}
