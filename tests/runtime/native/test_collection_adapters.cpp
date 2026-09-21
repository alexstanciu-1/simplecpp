#include "test_common.hpp"
#include "scpp/collections.hpp"

using namespace scpp;
using integer = int_t<>;
struct identity { integer operator()(integer value) const { return value; } };
struct predicate { bool_t operator()(integer) const { return bool_t(true); } };
struct wrong_predicate { integer operator()(integer value) const { return value; } };
template <typename C, typename F> concept sequence_mappable = requires(C c, F f) { collections::sequence_map(c, f); };
template <typename C, typename F> concept sequence_filterable = requires(C c, F f) { collections::sequence_filter(c, f); };
template <typename C, typename F> concept keyed_mappable = requires(C c, F f) { collections::keyed_map(c, f); };
template <typename C, typename F> concept keyed_filterable = requires(C c, F f) { collections::keyed_filter(c, f); };

template <typename C> static std::size_t entries(const C &source) {
	std::size_t count = 0;
	for ([[maybe_unused]] auto entry : foreach_range(source)) ++count;
	return count;
}
template <typename T, typename C, typename Map, typename Filter>
static void boundaries(const C &source, Map map, Filter filter) {
	const auto count = entries(source);
	auto mapped = map(source, [](T) -> string_t { return string_t("changed"); });
	assert(entries(mapped) == count);
	auto all = filter(source, [](T) -> bool_t { return bool_t(true); });
	auto none = filter(source, [](T) -> bool_t { return bool_t(false); });
	assert(entries(all) == count && entries(none) == 0 && entries(source) == count);
	int calls = 0;
	// Mutation here only instruments whether an empty input invokes a callback.
	assert(entries(map(none, [&calls](T) -> string_t { ++calls; return string_t("bad"); })) == 0);
	assert(entries(filter(none, [&calls](T) -> bool_t { ++calls; return bool_t(true); })) == 0);
	assert(calls == 0);
}
int main() {
	const auto seq_map = [](const auto &c, auto f) { return collections::sequence_map(c, f); };
	const auto seq_filter = [](const auto &c, auto f) { return collections::sequence_filter(c, f); };
	const auto key_map = [](const auto &c, auto f) { return collections::keyed_map(c, f); };
	const auto key_filter = [](const auto &c, auto f) { return collections::keyed_filter(c, f); };
	vector_t<integer> vector{integer(10), integer(20), integer(30)};
	fixed_array_t<integer, 3> fixed{integer(10), integer(20), integer(30)};
	boundaries<integer>(vector, seq_map, seq_filter);
	boundaries<integer>(fixed, seq_map, seq_filter);
	auto selected = collections::sequence_filter(fixed, [](integer n) -> bool_t { return n > integer(10); });
	static_assert(std::same_as<decltype(selected), vector_t<integer>>);
	assert(selected.at(0).native_value() == 20 && selected.at(1).native_value() == 30);
	auto strings = collections::sequence_map(vector, [](integer) -> string_t { return string_t("x"); });
	static_assert(std::same_as<decltype(strings), vector_t<string_t>>);
	auto strings_kept = collections::sequence_filter(strings, [](const string_t &s) -> bool_t { return bool_t(s.native_value() == "x"); });
	assert(strings_kept.size() == 3);
	vector_t<vector_t<integer>> nested; nested.append(vector);
	auto lengths = collections::sequence_map(nested, [](const vector_t<integer> &row) -> integer { return integer(row.size()); });
	assert(lengths.at(0).native_value() == 3);
	static_assert(!sequence_mappable<decltype(strings), decltype([](string_t &s) -> string_t { return s; })>);
	hash_t<integer, integer> dense;
	for (int i = 0; i < 3; ++i) dense.set(integer(i), integer((i + 1) * 10));
	boundaries<integer>(dense, key_map, key_filter);
	auto keyed = collections::keyed_filter(dense, [](integer n) -> bool_t { return n > integer(10); });
	auto it = keyed.begin_entries();
	assert((*it).key().native_value() == 1 && (*it).value_copy().native_value() == 20);
	++it; assert((*it).key().native_value() == 2);
	auto labels = collections::keyed_map(keyed, [](integer) -> string_t { return string_t("label"); });
	static_assert(std::same_as<decltype(labels), hash_t<string_t, integer>>);
	assert(labels.at(integer(1)).native_value() == "label");
	hash_t<integer> named; named.set(string_t("z"), integer(1)); named.set(string_t("a"), integer(2));
	boundaries<integer>(named, key_map, key_filter);
	auto named_copy = collections::keyed_map(named, identity{});
	auto named_it = named_copy.begin_entries(); assert((*named_it).key().native_value() == "z");
	++named_it; assert((*named_it).key().native_value() == "a");
	hash_t<mixed_t> table; table.set(integer(0), mixed_t(integer(10))); table.set(string_t("00"), mixed_t(integer(20)));
	boundaries<mixed_t>(table, key_map, key_filter);
	mixed_t mixed(std::make_unique<hash_t<mixed_t>>(table));
	boundaries<mixed_t>(mixed, key_map, key_filter);
	scpp_test::expect_throw<runtime_error>([] { (void)collections::keyed_map(mixed_t(integer(3)), [](mixed_t x) -> mixed_t { return x; }); });
	static_assert(!sequence_mappable<decltype(dense), identity>);
	static_assert(!sequence_filterable<decltype(dense), predicate>);
	static_assert(!keyed_mappable<decltype(vector), identity>);
	static_assert(!keyed_filterable<decltype(fixed), predicate>);
	static_assert(!sequence_filterable<decltype(vector), wrong_predicate>);
	static_assert(!keyed_filterable<decltype(dense), wrong_predicate>);
	static_assert(!keyed_mappable<dynamic_t<integer, integer>, identity>);
	static_assert(!sequence_mappable<integer, identity>);
}
