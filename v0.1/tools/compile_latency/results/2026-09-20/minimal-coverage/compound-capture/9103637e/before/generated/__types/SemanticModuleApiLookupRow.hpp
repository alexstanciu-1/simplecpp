#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SemanticModuleApiLookupRow {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> row_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> module_owner_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> availability_policy_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> contract_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> signature_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> source_consumption_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::int32_t> min_arity = cast<int_t<std::int32_t>>(static_cast<int_t<> >(0));
	int_t<std::int32_t> max_arity = cast<int_t<std::int32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> parameter_type_ref_count = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> return_type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	string_t parameter_type_ref_keys = string_t("");
	string_t source_name = string_t("");
	string_t runtime_target = string_t("");
	string_t module_owner_key = string_t("");
	string_t return_type_ref_key = string_t("");
	string_t authority_source = string_t("");
	string_t contract_authority_source = string_t("");
	string_t blocked_reason = string_t("");
};
}
