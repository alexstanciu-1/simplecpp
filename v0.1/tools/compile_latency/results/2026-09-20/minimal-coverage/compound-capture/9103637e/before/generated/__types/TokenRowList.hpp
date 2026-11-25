#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/TokenRow.hpp"
namespace scpp {
class TokenRowSegment;
class TokenRowList {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint32_t> row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> storage_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> segment_capacity = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(65536));
	int_t<std::uint32_t> segment_threshold = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(16384));
	int_t<std::uint32_t> segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> reserved_capacity = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<TokenRow> rows = vector_t<TokenRow>{};
	vector_t<shared_p<TokenRowSegment>> segments = vector_t<shared_p<TokenRowSegment>>{};
};
}
