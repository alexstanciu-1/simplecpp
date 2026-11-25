#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class ControlFlowLocalWriteSet {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	vector_t<int_t<std::uint32_t>> name_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> source_row_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> type_ref_ids = vector_t<int_t<std::uint32_t>>{};
};
}
