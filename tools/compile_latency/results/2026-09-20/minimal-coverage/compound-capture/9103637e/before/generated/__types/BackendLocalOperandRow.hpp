#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct BackendLocalOperandRow {
	BackendLocalOperandRow* operator->() { return this; }
	const BackendLocalOperandRow* operator->() const { return this; }
	int_t<std::uint32_t> owner_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> local_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> value_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::int32_t> value = cast<int_t<std::int32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> local_operation_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> flags = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
