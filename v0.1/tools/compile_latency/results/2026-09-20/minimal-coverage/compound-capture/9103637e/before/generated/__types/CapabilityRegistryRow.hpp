#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct CapabilityRegistryRow {
	CapabilityRegistryRow* operator->() { return this; }
	const CapabilityRegistryRow* operator->() const { return this; }
	int_t<std::uint16_t> capability_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
