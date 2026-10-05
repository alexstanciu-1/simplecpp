#include "scpp/runtime.hpp"

#include <cstdio>
#include <stdexcept>
#include <utility>

namespace {

// Retain behavioral checks under NDEBUG as well as in the debug build.
void require(bool condition, const char *message) {
	if (!condition) throw std::runtime_error(message);
}

void scalar_and_string_lifetime() {
	scpp::mixed_t value;
	require(value.is_null().native_value(), "default null");
	value = scpp::int_t<>{42LL};
	scpp::mixed_t copied(value);
	value = scpp::float_t{2.5};
	require(copied.int_value().native_value() == 42, "scalar copy independence");
	require(value.float_value().native_value() == 2.5, "scalar kind replacement");
	value = scpp::string_t{"a string longer than typical small string storage"};
	copied = value;
	value.get_string().append(scpp::string_t{" changed"});
	require(copied.get_string().native_value() == "a string longer than typical small string storage",
		"string copy independence");
	scpp::mixed_t moved(std::move(value));
	require(value.is_null().native_value(), "move constructor clears source");
	require(moved.get_string().native_value().ends_with(" changed"), "move retains string");
	value = std::move(moved);
	require(moved.is_null().native_value(), "move assignment clears source");
	const auto &self = value;
	value = self;
	require(value.get_string().native_value().ends_with(" changed"), "self assignment");
	value = scpp::bool_t{true};
	require(value.bool_value().native_value(), "owned string to inline bool");
}

void container_lifetime() {
	auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	table->set(scpp::string_t{"n"}, scpp::mixed_t{scpp::int_t<>{7LL}});
	scpp::mixed_t value{table};
	scpp::mixed_t copy{value};
	copy[scpp::string_t{"n"}] = scpp::mixed_t{scpp::int_t<>{8LL}};
	require(value.get(scpp::string_t{"n"}).int_value().native_value() == 7, "COW original");
	require(copy.get(scpp::string_t{"n"}).int_value().native_value() == 8, "COW copy");

	scpp::mixed_t owned{scpp::unique<scpp::hash_t<scpp::mixed_t>>()};
	owned[scpp::string_t{"n"}] = scpp::mixed_t{scpp::int_t<>{9LL}};
	scpp::mixed_t owned_copy{owned};
	owned_copy[scpp::string_t{"n"}] = scpp::mixed_t{scpp::int_t<>{10LL}};
	require(owned.get(scpp::string_t{"n"}).int_value().native_value() == 9, "owned table copy");

	// Historical dynamic carrier stays unchanged in this layout-only checkpoint.
	// Family separation is a distinct refactor, not implied by these assertions.
	const auto before = table.use_count();
	{
		scpp::mixed_t object{scpp::dynamic_box(table)};
		scpp::mixed_t alias{object};
		alias[scpp::string_t{"n"}] = scpp::mixed_t{scpp::int_t<>{11LL}};
		require(object.get(scpp::string_t{"n"}).int_value().native_value() == 11, "shared identity");
		scpp::mixed_t moved{std::move(alias)};
		require(alias.is_null().native_value(), "container move clears source");
		moved = scpp::null_t{};
	}
	require(table.use_count() == before, "shared carrier destruction balances ownership");
	scpp::mixed_t weak{scpp::weak(table)};
	scpp::mixed_t weak_copy{weak};
	require(weak_copy.weak_table_if()->lock().get() == table.get(), "weak carrier copy");
}

} // namespace

int main() {
	// The compact-layout target applies to the measured 64-bit wrapper ABI;
	// do not impose that byte count on unrelated targets or standard libraries.
	constexpr bool target_abi = sizeof(void *) == 8
		&& sizeof(scpp::shared_p<scpp::hash_t<scpp::mixed_t>>) == 16
		&& alignof(scpp::shared_p<scpp::hash_t<scpp::mixed_t>>) == 8;
	static_assert(!target_abi || sizeof(scpp::mixed_t) == 24);
	static_assert(!target_abi || alignof(scpp::mixed_t) == 8);
	scalar_and_string_lifetime();
	container_lifetime();
	std::printf("mixed_t: size=%zu align=%zu; lifetime/copy checks passed\n",
		sizeof(scpp::mixed_t), alignof(scpp::mixed_t));
}
