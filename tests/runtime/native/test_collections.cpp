#include "test_common.hpp"
#include "scpp/collections.hpp"

using namespace scpp;
using integer = int_t<>;

template <typename T, typename Collection>
static void check_filter_boundaries(const Collection &input, std::size_t expected) {
	// Counter mutation is test instrumentation, not application callback guidance.
	std::size_t calls = 0;
	auto all = collections::filter(input, [&calls](T) -> bool_t { ++calls; return bool_t(true); });
	assert(calls == expected);
	std::size_t count = 0;
	for ([[maybe_unused]] auto entry : foreach_range(all)) ++count;
	assert(count == expected);
	calls = 0;
	auto none = collections::filter(input, [&calls](T) -> bool_t { ++calls; return bool_t(false); });
	assert(calls == expected);
	count = 0;
	for ([[maybe_unused]] auto entry : foreach_range(none)) ++count;
	assert(count == 0);
	// Every carrier's empty result remains valid input and invokes no callbacks.
	calls = 0;
	auto empty = collections::map(none, [&calls](T value) -> T { ++calls; return value; });
	for ([[maybe_unused]] auto entry : foreach_range(empty)) ++count;
	assert(count == 0 && calls == 0);
}

int main() {
	const vector_t<integer> input{integer(1), integer(2), integer(3)};
	auto mapped = collections::map(input, [](integer n) -> string_t { return string_t(std::to_string(n.native_value())); });
	static_assert(std::same_as<decltype(mapped), vector_t<string_t>>);
	assert(mapped.at(1).native_value() == "2");
	auto kept = collections::filter(input, [](integer n) -> bool_t { return bool_t(n.native_value() != 2); });
	assert(kept.size() == 2 && kept.at(1).native_value() == 3);
	assert(input.at(1).native_value() == 2);
	const fixed_array_t<integer, 2> fixed{integer(7), integer(9)};
	auto dense = collections::filter(fixed, [](integer n) -> bool_t { return bool_t(n.native_value() > 8); });
	static_assert(std::same_as<decltype(dense), vector_t<integer>>);
	assert(dense.size() == 1 && dense.at(0).native_value() == 9);

	hash_t<integer, integer> keyed;
	keyed.set(integer(8), integer(2)); keyed.set(integer(3), integer(1));
	auto keys = collections::map(keyed, [](integer n) -> string_t { return string_t(std::to_string(n.native_value())); });
	static_assert(std::same_as<decltype(keys), hash_t<string_t, integer>>);
	assert(keys.at(integer(8)).native_value() == "2");
	auto it = keys.begin_entries(); assert((*it).key().native_value() == 8); ++it; assert((*it).key().native_value() == 3);

	hash_t<mixed_t> table;
	table.set(integer(4), mixed_t(integer(1))); table.set(string_t("04"), mixed_t(integer(2)));
	auto boxed = collections::map(table, [](mixed_t) -> string_t { return string_t("boxed"); });
	static_assert(std::same_as<decltype(boxed), hash_t<mixed_t>>);
	assert(boxed.size() == 2);
	mixed_t mixed(std::make_unique<hash_t<mixed_t>>(table));
	auto mixed_out = collections::filter(mixed, [](mixed_t) -> bool_t { return bool_t(true); });
	assert(mixed_out.try_get_hash() != mixed.try_get_hash());
	assert(mixed_out.try_get_hash()->size() == 2);
	dynamic_t<> dynamic(std::make_shared<hash_t<mixed_t>>(table));
	auto dynamic_out = collections::map(dynamic, [](mixed_t) -> integer { return integer(5); });
	assert(dynamic_out.native_value() != dynamic.native_value());
	assert(dynamic_out->size() == 2);
	const dynamic_t<integer, integer> typed(std::make_shared<hash_t<integer, integer>>(keyed));
	auto typed_out = collections::map(typed, [](integer) -> string_t { return string_t("x"); });
	static_assert(std::same_as<decltype(typed_out), dynamic_t<string_t, integer>>);
	assert(typed_out->at(integer(8)).native_value() == "x");
	check_filter_boundaries<integer>(input, 3);
	check_filter_boundaries<integer>(fixed, 2);
	check_filter_boundaries<integer>(keyed, 2);
	check_filter_boundaries<mixed_t>(table, 2);
	check_filter_boundaries<mixed_t>(mixed, 2);
	check_filter_boundaries<mixed_t>(dynamic, 2);
	check_filter_boundaries<integer>(typed, 2);

	vector_t<shared_p<scpp_test::sample_object>> objects;
	objects.append(shared_p<scpp_test::sample_object>(std::make_shared<scpp_test::sample_object>(integer(7))));
	auto object_copy = collections::map(objects, [](shared_p<scpp_test::sample_object> item) -> shared_p<scpp_test::sample_object> { return item; });
	assert(&object_copy.at(0) != &objects.at(0));
	assert(object_copy.at(0).native_value() == objects.at(0).native_value());
	assert(objects.at(0)->value.native_value() == 7);

	objects.append(shared_p<scpp_test::sample_object>(std::make_shared<scpp_test::sample_object>(integer(9))));
	const auto owners_before = objects.at(0).native_value().use_count();
	scpp_test::expect_throw<std::logic_error>([&] {
		(void)collections::map(objects, [](shared_p<scpp_test::sample_object> item) -> shared_p<scpp_test::sample_object> {
			if (item->value.native_value() == 9) { throw std::logic_error("partial output"); }
			return item;
		});
	});
	assert(objects.at(0).native_value().use_count() == owners_before);

	int calls = 0;
	const vector_t<integer> empty;
	auto empty_out = collections::map(empty, [&calls](integer n) -> integer { ++calls; return n; });
	assert(empty_out.size() == 0 && calls == 0);
	scpp_test::expect_throw<runtime_error>([] { (void)collections::map(mixed_t(integer(2)), [](mixed_t x) -> mixed_t { return x; }); });
	scpp_test::expect_throw<runtime_error>([] { (void)collections::filter(dynamic_t<>(), [](mixed_t) -> bool_t { return bool_t(true); }); });
	scpp_test::expect_throw<std::logic_error>([&] {
		(void)collections::map(input, [&calls](integer n) -> integer { if (++calls == 2) throw std::logic_error("callback"); return n; });
	});
	assert(calls == 2 && input.size() == 3);
	static_assert(!collections::detail::compatible_callback<vector_t<integer>, decltype([](mixed_t) -> integer { return integer(1); })>);
	static_assert(!collections::detail::compatible_callback<vector_t<integer>, decltype([](integer &) -> integer { return integer(1); })>);
}
