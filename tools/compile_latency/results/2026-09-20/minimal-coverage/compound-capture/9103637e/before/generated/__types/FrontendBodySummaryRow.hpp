#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct FrontendBodySummaryRow {
	FrontendBodySummaryRow* operator->() { return this; }
	const FrontendBodySummaryRow* operator->() const { return this; }
	int_t<std::uint32_t> summary_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> declaration_node_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> scope_node_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> statement_node_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> parent_statement_node_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> target_node_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> target_local_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> value_node_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> value_type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> condition_node_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> condition_type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> summary_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> statement_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> operator_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> feature_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> blocked_reason_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
