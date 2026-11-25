#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct PhsParserCursor {
	PhsParserCursor* operator->() { return this; }
	const PhsParserCursor* operator->() const { return this; }
	int_t<std::uint32_t> index = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
};
}
