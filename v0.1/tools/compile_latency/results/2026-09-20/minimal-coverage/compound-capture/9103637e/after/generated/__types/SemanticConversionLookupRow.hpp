#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SemanticConversionLookupRow {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> conversion_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> form_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> permission_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> lossiness_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> lowering_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> target_type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> authority_index = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	bool_t implicit_allowed = bool_t(static_cast<bool_t>(false));
	bool_t explicit_allowed = bool_t(static_cast<bool_t>(false));
	bool_t requires_runtime_check = bool_t(static_cast<bool_t>(false));
	string_t source_runtime_type = string_t("");
	string_t target_runtime_type = string_t("");
	string_t cast_name = string_t("");
	string_t policy_role = string_t("");
	string_t llvm_opcode = string_t("");
	string_t runtime_symbol = string_t("");
	string_t diagnostic_key = string_t("");
	string_t authority_source = string_t("");
};
}
