#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct TokenExtendedLengthRow {
	TokenExtendedLengthRow* operator->() { return this; }
	const TokenExtendedLengthRow* operator->() const { return this; }
	int_t<std::uint32_t> token_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> length = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
};
}
