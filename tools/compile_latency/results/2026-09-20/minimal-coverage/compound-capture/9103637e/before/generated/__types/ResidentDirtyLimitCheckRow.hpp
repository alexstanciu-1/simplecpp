#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct ResidentDirtyLimitCheckRow {
	ResidentDirtyLimitCheckRow* operator->() { return this; }
	const ResidentDirtyLimitCheckRow* operator->() const { return this; }
	int_t<std::uint32_t> check_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> owner_run_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> budget_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> observed_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> limit_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> limit_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
