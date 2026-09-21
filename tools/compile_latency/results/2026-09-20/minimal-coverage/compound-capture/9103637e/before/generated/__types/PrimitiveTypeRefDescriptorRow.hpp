#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct PrimitiveTypeRefDescriptorRow {
	PrimitiveTypeRefDescriptorRow* operator->() { return this; }
	const PrimitiveTypeRefDescriptorRow* operator->() const { return this; }
	int_t<std::uint32_t> type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> type_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
