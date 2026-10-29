#include "token.hpp"
#include <stdexcept>
#include <type_traits>

using namespace scpp;
using compiler_support::token;
using compiler_support::token_kind;

static void require(bool value)
{
	if (!value) {
		throw std::runtime_error("Compact token proof failed");
	}
}

static void rejects(uint64 offset, uint64 length, token_kind kind)
{
	try {
		token value{offset, length, kind};
	}
	catch (const std::out_of_range &) {
		return;
	}
	throw std::runtime_error("Invalid token was accepted");
}

int main()
{
	static_assert(sizeof(token) == 8 && alignof(token) == 4);
	static_assert(std::is_trivially_copyable_v<token>);
	static_assert(!std::is_convertible_v<int, int64>);
	static_assert(!std::is_convertible_v<uint64, uint32>);
	static_assert(std::is_same_v<decltype(uint32{} | uint32{}), uint32>);
	static_assert(std::is_same_v<decltype(uint64{} > uint64{}), bool_t>);
	static_assert(sizeof(int32) == 4 && sizeof(uint32) == 4);
	static_assert(sizeof(int64) == 8 && sizeof(uint64) == 8);

	// Every nonzero byte tag roundtrips; spelling classification belongs to the scanner.
	for (unsigned tag = 1; tag <= 255; ++tag) {
		const token value{uint64{0xffffffffULL}, uint64{0xffffff}, static_cast<token_kind>(tag)};
		require(value.offset().native_value() == 0xffffffffU);
		require(value.length().native_value() == 0xffffffU);
		require(static_cast<unsigned>(value.kind()) == tag);
		const token copy = value;
		require(copy.length().native_value() == value.length().native_value());
	}
	const token first{uint64{0}, uint64{1}, token_kind::identifier};
	require(first.offset().native_value() == 0 && first.length().native_value() == 1);
	rejects(uint64{0x100000000ULL}, uint64{1}, token_kind::identifier);
	rejects(uint64{0}, uint64{0}, token_kind::identifier);
	rejects(uint64{0}, uint64{0x1000000}, token_kind::identifier);
	rejects(uint64{0}, uint64{1}, token_kind::reserved);
	try {
		(void)static_cast<uint32>(uint64{0x100000000ULL});
		return 1;
	}
	catch (const std::out_of_range &) {
	}
	return 0;
}
