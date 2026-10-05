#include "scpp/support/dynamic_value.hpp"
#include "scpp/hash_t.hpp"
#include <stdexcept>
#include <type_traits>
#include <utility>

namespace {
using value = scpp::detail::dynamic_value;
using scpp::int_t;
using scpp::string_t;

void require(bool condition, const char *message) {
	if (!condition) throw std::runtime_error(message);
}

static_assert(!std::is_constructible_v<value, scpp::mixed_t>);
static_assert(!std::is_assignable_v<value &, scpp::mixed_t>);
static_assert(!std::is_constructible_v<scpp::mixed_t, value>);
static_assert(!std::is_constructible_v<value, scpp::dynamic_t<>>);
static_assert(std::is_nothrow_move_constructible_v<value>);
static_assert(std::is_nothrow_move_assignable_v<value>);

void scalar_lifetimes() {
	// This checkpoint measures the agreed target ABI, not a portable language promise.
	require(sizeof(value) == 24 && alignof(value) == 8, "dynamic target layout");
	value null;
	require(null.is_null().native_value(), "default is null");
	value flag{scpp::bool_t{false}};
	require(flag.kind() == value::kind_t::bool_v && !flag.get_bool().native_value(), "false distinct from null");
	value number{int_t<>{42LL}};
	value copied{number};
	number = value{scpp::float_t{2.5}};
	require(copied.get_int().native_value() == 42 && number.get_float().native_value() == 2.5, "scalar value copy");
	value text{string_t{"hello"}};
	value text_copy{text};
	require(&text.get_string() != &text_copy.get_string(), "owned string wrapper copy");
	text = value{};
	require(text_copy.get_string().native_value() == "hello", "copied string lifetime");
	value moved{std::move(text_copy)};
	require(text_copy.is_null().native_value(), "move constructor leaves one null state");
	const auto &self = moved;
	moved = self;
	require(moved.get_string().native_value() == "hello", "self copy");
	auto &move_self = moved;
	moved = std::move(move_self);
	require(moved.get_string().native_value() == "hello", "self move");
	number = std::move(moved);
	require(moved.is_null().native_value() && number.get_string().native_value() == "hello", "move assignment");
	bool rejected = false;
	try { (void)number.get_int(); } catch (const std::runtime_error &) { rejected = true; }
	require(rejected && number.get_string().native_value() == "hello", "exact access failure preserves value");
	rejected = false;
	try { (void)null.get_table(); } catch (const std::runtime_error &) { rejected = true; }
	require(rejected && null.is_null().native_value(), "null does not create storage");
}

void recursive_containers() {
	value root = value::make_container();
	require(!root.is_null().native_value() && root.get_table().empty().native_value(), "empty container differs from null");
	require(root.get_table().is_packed().native_value(), "shape uses table query");
	value child = value::make_container();
	(void)child.get_table().append(value{int_t<>{7LL}});
	root.get_table().set(string_t{"child"}, child);
	value alias = root;
	require(alias.table_if() == root.table_if(), "container copy preserves identity");
	value selected = alias.get_table().read_or_default(string_t{"child"});
	selected.get_table().set(int_t<>{0LL}, value{int_t<>{9LL}});
	require(child.get_table().at(int_t<>{0LL}).get_int().native_value() == 9, "nested child aliases");
	static_assert(std::is_same_v<decltype(selected), value>);
	require(root.get_table().read_or_default(string_t{"missing"}).is_null().native_value(), "same-family null miss");
	require(root.get_table().size() == 1, "read miss does not insert");
	root = value{int_t<>{100LL}};
	require(alias.get_table().size() == 1 && root.get_int().native_value() == 100, "replacement only rebinds variable");
	alias.get_table().remove(string_t{"child"});
	require(selected.get_table().size() == 1, "child survives parent removal");
	value moved = std::move(selected);
	require(selected.is_null().native_value() && moved.table_if() == child.table_if(), "container move");
	for (int i = 0; i < 300; ++i) {
		alias.get_table().set(string_t{std::to_string(i)}, value{int_t<>{static_cast<std::int64_t>(i)}});
	}
	int expected = 0;
	for (auto it = alias.get_table().begin_entries(); it != alias.get_table().end_entries(); ++it) {
		const value element = (*it).value_copy();
		require(element.get_int().native_value() == expected++, "iteration gives same-family copied values");
	}
	require(expected == 300, "growth and tombstone traversal");
}
} // namespace

int main() {
	scalar_lifetimes();
	recursive_containers();
}
