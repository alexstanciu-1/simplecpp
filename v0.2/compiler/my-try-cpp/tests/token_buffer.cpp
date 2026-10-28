#include "scpp_compiler/token_buffer.hpp"
#include <limits>
#include <stdexcept>
#include <type_traits>
#include <utility>

using namespace scpp;
using compiler_support::token_buffer;

static void require(bool value)
{
	if (!value) {
		throw std::runtime_error("Source snapshot proof failed");
	}
}

template<class Exception, class F> static void rejects(F work)
{
	try {
		work();
	}
	catch (const Exception &) {
		return;
	}
	throw std::runtime_error("Invalid source operation accepted");
}

int main()
{
	static_assert(std::is_nothrow_move_constructible_v<token_buffer>);
	static_assert(std::is_nothrow_move_assignable_v<token_buffer>);
	static_assert(!std::is_trivially_copyable_v<token_buffer>);
	const uint64 huge{std::numeric_limits<unsigned long long>::max()};
	token_buffer absent;
	require(!static_cast<bool>(absent.present()));
	require(absent.byte_size().native_value() == 0);
	require(absent.peek(uint64{0}).native_value() == -1);
	require(!static_cast<bool>(absent.matches(uint64{0}, uint64{0}, "")));
	require(!static_cast<bool>(absent.same_storage(absent)));
	rejects<std::out_of_range>([&] { (void)absent.byte_at(uint64{0}); });

	const auto empty = token_buffer::from_bytes("", uint64{0});
	require(static_cast<bool>(empty.present()));
	require(empty.byte_size().native_value() == 0);
	require(static_cast<bool>(empty.matches(uint64{0}, uint64{0}, "")));
	require(empty.peek(uint64{0}).native_value() == -1);

	token_buffer retained;
	{
		char bytes[] = {'A', '\0', static_cast<char>(0xff), 'Z'};
		auto source = token_buffer::from_bytes(bytes, uint64{4});
		bytes[0] = 'B';
		require(source.peek(uint64{0}).native_value() == 'A');
		require(source.peek(uint64{1}).native_value() == 0);
		require(source.peek(uint64{2}).native_value() == 255);
		require(source.byte_at(uint64{2}).native_value() == 255);
		require(source.peek(uint64{4}).native_value() == -1);
		require(source.peek(huge).native_value() == -1);
		rejects<std::out_of_range>([&] { (void)source.byte_at(huge); });
		require(static_cast<bool>(source.matches(uint64{3}, uint64{1}, "Z")));
		require(!static_cast<bool>(source.matches(uint64{3}, uint64{1}, "ZZ")));
		require(static_cast<bool>(source.matches(uint64{4}, uint64{0}, "")));
		require(!static_cast<bool>(source.matches(huge, uint64{1}, "Z")));
		require(!static_cast<bool>(source.matches(uint64{1}, huge, "Z")));
		retained = source;
		require(static_cast<bool>(retained.same_storage(source)));
		const token_buffer copy(source);
		require(static_cast<bool>(copy.same_storage(source)));
		auto moved = std::move(source);
		require(!static_cast<bool>(source.present()));
		require(static_cast<bool>(moved.same_storage(retained)));
		source = std::move(moved);
		require(!static_cast<bool>(moved.present()));
		auto &self = source;
		source = self;
		source = std::move(self);
		require(static_cast<bool>(source.same_storage(retained)));
		source = token_buffer::from_bytes("new", uint64{3});
		require(!static_cast<bool>(source.same_storage(retained)));
	}
	require(retained.byte_size().native_value() == 4);
	require(retained.peek(uint64{0}).native_value() == 'A');
	const auto independent = token_buffer::from_bytes("A\0\xffZ", uint64{4});
	require(!static_cast<bool>(retained.same_storage(independent)));
	retained.swap(absent);
	require(!static_cast<bool>(retained.present()));
	require(absent.byte_at(uint64{3}).native_value() == 'Z');
	retained = absent;
	absent = token_buffer{};
	require(retained.byte_size().native_value() == 4);

	rejects<std::invalid_argument>([] { (void)token_buffer::from_bytes(nullptr, uint64{0}); });
	rejects<std::invalid_argument>([] { (void)token_buffer::from_bytes(nullptr, uint64{1}); });
	rejects<std::invalid_argument>([&] { (void)retained.matches(uint64{0}, uint64{0}, nullptr); });
	rejects<std::length_error>([&] { (void)token_buffer::from_bytes("", huge); });
	return 0;
}
