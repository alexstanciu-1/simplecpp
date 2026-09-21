#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct OperationBlockedReasonRouteRow {
	OperationBlockedReasonRouteRow* operator->() { return this; }
	const OperationBlockedReasonRouteRow* operator->() const { return this; }
	int_t<std::uint16_t> capability_blocked_reason_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> operation_blocked_reason_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
