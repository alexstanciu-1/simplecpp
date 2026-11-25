#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct BackendStepRouteDescriptorRow {
	BackendStepRouteDescriptorRow* operator->() { return this; }
	const BackendStepRouteDescriptorRow* operator->() const { return this; }
	int_t<std::uint16_t> feature_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> lowering_step_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
