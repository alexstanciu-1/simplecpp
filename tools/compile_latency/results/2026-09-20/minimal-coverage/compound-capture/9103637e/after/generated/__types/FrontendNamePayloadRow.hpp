#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct FrontendNamePayloadRow {
	FrontendNamePayloadRow* operator->() { return this; }
	const FrontendNamePayloadRow* operator->() const { return this; }
	int_t<std::uint32_t> payload_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> name_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_range_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> name_role_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> flags = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
