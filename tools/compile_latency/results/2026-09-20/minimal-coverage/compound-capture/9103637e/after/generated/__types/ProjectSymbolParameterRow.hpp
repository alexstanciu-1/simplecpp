#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct ProjectSymbolParameterRow {
	ProjectSymbolParameterRow* operator->() { return this; }
	const ProjectSymbolParameterRow* operator->() const { return this; }
	int_t<std::uint32_t> parameter_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> symbol_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> parameter_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> default_literal_source_row_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> default_literal_type_ref_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::int32_t> default_literal_numeric_payload = cast<int_t<std::int32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> default_literal_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> position = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> flags = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
