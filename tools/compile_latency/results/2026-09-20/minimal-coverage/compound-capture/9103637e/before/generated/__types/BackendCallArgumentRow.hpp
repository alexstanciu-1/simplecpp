#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct BackendCallArgumentRow {
	BackendCallArgumentRow* operator->() { return this; }
	const BackendCallArgumentRow* operator->() const { return this; }
	int_t<std::uint32_t> owner_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> argument_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::int32_t> value = cast<int_t<std::int32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> position = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> flags = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
