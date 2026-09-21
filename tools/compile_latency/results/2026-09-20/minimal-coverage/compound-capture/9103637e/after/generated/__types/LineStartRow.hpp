#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct LineStartRow {
	LineStartRow* operator->() { return this; }
	const LineStartRow* operator->() const { return this; }
	int_t<std::uint32_t> source_buffer_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> line = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> start_offset = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
};
}
