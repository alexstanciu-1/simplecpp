#include "token_sequence.hpp"
#include <stdexcept>
#include <limits>
#include <type_traits>
#include <utility>

using namespace scpp;
using namespace scpp::compiler_support;

static void require(bool value)
{
	if (!value) {
		throw std::runtime_error("Token generation proof failed");
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
	throw std::runtime_error("Invalid token generation operation accepted");
}

int main()
{
	static_assert(!std::is_copy_constructible_v<token_builder>);
	static_assert(!std::is_move_constructible_v<token_builder>);
	static_assert(std::is_nothrow_move_constructible_v<token_sequence>);
	static_assert(std::is_nothrow_move_assignable_v<token_sequence>);
	token_sequence missing;
	require(!static_cast<bool>(missing.present()));
	require(missing.size().native_value() == 0 && missing.capacity_bytes().native_value() == 0);
	rejects<std::out_of_range>([&] { (void)missing.at(int64{0}); });
	rejects<std::logic_error>([&] { (void)missing.source(); });
	rejects<std::out_of_range>([] { token_builder invalid{token_buffer{}, int64{0}}; });
	const auto empty = token_buffer::from_bytes("", uint64{0});
	token_builder empty_builder{empty, int64{999}};
	auto empty_generation = empty_builder.finish();
	require(static_cast<bool>(empty_generation.present()));
	require(empty_generation.size().native_value() == 0);
	require(static_cast<bool>(empty_generation.source().same_storage(empty)));
	rejects<std::out_of_range>([&] { (void)empty_generation.at(int64{0}); });

	token_sequence retained;
	token_buffer retained_source;
	{
		auto source = token_buffer::from_bytes("one two;", uint64{8});
		rejects<std::out_of_range>([&] { token_builder invalid{source, int64{-1}}; });
		token_builder builder{source, int64{0}};
		builder.append(uint64{0}, uint64{3}, token_kind::identifier);
		rejects<std::out_of_range>([&] { builder.append(uint64{2}, uint64{2}, token_kind::identifier); });
		rejects<std::out_of_range>([&] { builder.append(uint64{0}, uint64{1}, token_kind::identifier); });
		rejects<std::out_of_range>([&] { builder.append(uint64{4}, uint64{0}, token_kind::identifier); });
		rejects<std::out_of_range>([&] { builder.append(uint64{4}, uint64{3}, token_kind::reserved); });
		rejects<std::out_of_range>([&] { builder.append(uint64{7}, uint64{2}, token_kind::identifier); });
		const uint64 huge{std::numeric_limits<unsigned long long>::max()};
		rejects<std::out_of_range>([&] { builder.append(huge, uint64{1}, token_kind::identifier); });
		rejects<std::out_of_range>([&] { builder.append(uint64{1}, huge, token_kind::identifier); });
		builder.append(uint64{4}, uint64{3}, token_kind::identifier);
		builder.append(uint64{7}, uint64{1}, token_kind::semicolon);
		auto sequence = builder.finish();
		require(sequence.size().native_value() == 3);
		require(sequence.capacity_bytes().native_value() >= 3 * sizeof(token));
		require(sequence.at(int64{1}).offset().native_value() == 4);
		require(sequence.at(int64{2}).kind() == token_kind::semicolon);
		rejects<std::logic_error>([&] { (void)builder.finish(); });
		rejects<std::logic_error>([&] { builder.append(uint64{0}, uint64{1}, token_kind::identifier); });
		rejects<std::out_of_range>([&] { (void)sequence.at(int64{-1}); });
		rejects<std::out_of_range>([&] { (void)sequence.at(int64{3}); });
		retained = sequence;
		retained_source = sequence.source();
		require(static_cast<bool>(retained.same_storage(sequence)));
		require(static_cast<bool>(retained_source.same_storage(source)));
		token_sequence copy(sequence);
		auto moved = std::move(copy);
		require(!static_cast<bool>(copy.present()));
		copy = std::move(moved);
		require(!static_cast<bool>(moved.present()));
		auto &self = copy;
		copy = self;
		copy = std::move(self);
		require(static_cast<bool>(copy.same_storage(sequence)));
		source = token_buffer{};
		sequence = empty_generation;
		require(!static_cast<bool>(retained.same_storage(sequence)));
	}
	require(retained.at(int64{0}).length().native_value() == 3);
	require(static_cast<bool>(retained_source.matches(uint64{0}, uint64{3}, "one")));
	retained.swap(missing);
	require(!static_cast<bool>(retained.present()));
	missing = token_sequence{};
	require(retained_source.byte_size().native_value() == 8);

	// Exercise growth beyond the initial reserve and compare every span after publication.
	char bytes[600]{};
	const auto source = token_buffer::from_bytes(bytes, uint64{600});
	token_builder growing{source, int64{0}};
	for (unsigned i = 0; i < 600; ++i) {
		growing.append(uint64{i}, uint64{1}, token_kind::identifier);
	}
	const auto grown = growing.finish();
	require(grown.size().native_value() == 600);
	for (unsigned i = 0; i < 600; ++i) {
		require(grown.at(int64{i}).offset().native_value() == i);
	}
	return 0;
}
