#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct BackendBinaryOperandRow {
	BackendBinaryOperandRow* operator->() { return this; }
	const BackendBinaryOperandRow* operator->() const { return this; }
	int_t<std::uint32_t> owner_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> left_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> right_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::int32_t> left_value = cast<int_t<std::int32_t>>(static_cast<int_t<> >(0));
	int_t<std::int32_t> right_value = cast<int_t<std::int32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> flags = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
