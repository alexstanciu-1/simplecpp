#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct FeatureOperationRouteDescriptorRow {
	FeatureOperationRouteDescriptorRow* operator->() { return this; }
	const FeatureOperationRouteDescriptorRow* operator->() const { return this; }
	int_t<std::uint16_t> feature_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> route_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> contract_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> operation_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> lowering_adapter_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> default_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
