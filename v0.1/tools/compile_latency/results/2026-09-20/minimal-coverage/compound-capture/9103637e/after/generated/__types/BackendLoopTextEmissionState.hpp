#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class BackendLoopTextEmissionState {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t condition_label = string_t("");
	string_t continue_label = string_t("");
	string_t exit_label = string_t("");
	int_t<std::uint32_t> close_after_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> continue_close_after_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> terminator_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
