#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct ProjectSymbolBodyLookupRow {
	ProjectSymbolBodyLookupRow* operator->() { return this; }
	const ProjectSymbolBodyLookupRow* operator->() const { return this; }
	int_t<std::uint32_t> lookup_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> symbol_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> lookup_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> blocked_reason_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> function_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
};
}
