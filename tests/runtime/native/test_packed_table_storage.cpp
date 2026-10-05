#include "scpp/runtime.hpp"

#include <stdexcept>
#include <type_traits>
#include <utility>

namespace {

void require(bool condition, const char *message) {
	if (!condition) throw std::runtime_error(message);
}

// No mixed conversion: storage reuse must not rely on boxing a family's values.
struct independent_value {
	int number = 0;
	inline static bool reject_copy = false;
	independent_value() = default;
	explicit independent_value(int value) : number(value) {}
	independent_value(const independent_value &other) : number(other.number) {
		if (reject_copy) throw std::runtime_error("copy rejected");
	}
	independent_value(independent_value &&) noexcept = default;
	independent_value &operator=(const independent_value &) = default;
	independent_value &operator=(independent_value &&) noexcept = default;
};

using storage = scpp::hash_detail::packed_table<independent_value>;
static_assert(!std::is_constructible_v<scpp::mixed_t, independent_value>);
static_assert(!std::is_convertible_v<scpp::hash_t<scpp::mixed_t> *,
	scpp::hash_detail::packed_table<scpp::mixed_t> *>);
static_assert(!std::is_polymorphic_v<storage>);
static_assert(sizeof(scpp::hash_t<scpp::mixed_t>)
	== sizeof(scpp::hash_detail::packed_table<scpp::mixed_t>));

void shape_keys_and_lookup() {
	storage values;
	require(values.is_packed().native_value(), "empty packed shape");
	require(values.append(independent_value{10}).native_value() == 0, "first append key");
	(void)values.append(independent_value{20});
	values.set(scpp::int_t<>{0LL}, independent_value{30});
	require(values.is_packed().native_value(), "replacement preserves packed shape");
	const auto &read = values;
	require(read.read_or_default(scpp::int_t<>{0LL}).number == 30, "copied read");
	require(read[scpp::int_t<>{99LL}].number == 0 && values.size() == 2, "const miss does not insert");
	require(!read.find(scpp::int_t<>{99LL}).has_value().native_value(), "find miss");
	require(read.find(scpp::int_t<>{1LL}).require_value("test find").number == 20, "find hit");
	bool missing = false;
	try { (void)read.at(scpp::string_t{"missing"}); }
	catch (const std::out_of_range &) { missing = true; }
	require(missing, "checked missing read");
	bool unsupported_reference = false;
	try { (void)read.try_ref(scpp::mixed_t{scpp::int_t<>{0LL}}); }
	catch (const std::runtime_error &) { unsupported_reference = true; }
	require(unsupported_reference, "reference rejection uses key type, not value type");
	values.set(scpp::string_t{"label"}, independent_value{40});
	require(!values.is_packed().native_value(), "string key changes shape");
	values.set(scpp::string_t{"10"}, independent_value{50});
	values.set(scpp::string_t{"08"}, independent_value{60});
#if defined(SCPP_LANGUAGE_TARGET_PHP) && SCPP_LANGUAGE_TARGET_PHP
	require(values.at(scpp::int_t<>{10LL}).number == 50, "numeric string normalization reused");
#else
	require(!values.has(scpp::int_t<>{10LL}).native_value(), "non-PHP key identity reused");
#endif
	require(values.at(scpp::string_t{"08"}).number == 60, "leading zero key stays text");
	require(!values.has(scpp::int_t<>{8LL}).native_value(), "leading zero key not integer");
	bool bad_key = false;
	try { values.set(scpp::mixed_t{false}, independent_value{99}); }
	catch (const std::runtime_error &) { bad_key = true; }
	require(bad_key && values.size() == 5, "wrong-kind key rejection");
}

void growth_removal_iteration_and_copy() {
	storage values;
	for (int i = 0; i < 600; ++i) {
		values.set(scpp::string_t{std::string("key") + std::to_string(i)}, independent_value{i});
	}
	for (int i = 0; i < 600; i += 2) {
		require(values.remove(scpp::string_t{std::string("key") + std::to_string(i)}), "remove existing");
	}
	require(!values.remove(scpp::string_t{"key0"}), "remove miss");
	require(values.size() == 300, "live size excludes tombstones");
	int expected = 1;
	const auto &read = values;
	for (auto it = read.begin_entries(); it != read.end_entries(); ++it) {
		auto entry = *it;
		require(entry.key().get_string().native_value() == std::string("key") + std::to_string(expected), "insertion order");
		require(entry.value_copy().number == expected, "entry value type");
		expected += 2;
	}
	require(expected == 601, "iteration skips all tombstones");
	storage copied{values};
	copied.set(scpp::string_t{"key1"}, independent_value{1000});
	require(values.at(scpp::string_t{"key1"}).number == 1, "independent container copy");
	storage moved{std::move(copied)};
	require(moved.at(scpp::string_t{"key1"}).number == 1000, "move retains values");
	storage assigned;
	assigned = moved;
	const auto &self = assigned;
	assigned = self;
	require(assigned.size() == 300, "copy and self assignment preserve entries");
	assigned.clear();
	require(assigned.empty().native_value() && assigned.is_packed().native_value(), "clear resets shape");
}

void copy_failure_and_wrapper_surface() {
	storage values;
	values.set(scpp::string_t{"key"}, independent_value{1});
	independent_value value{2};
	independent_value::reject_copy = true;
	bool failed = false;
	try { values.set(scpp::string_t{"key"}, value); }
	catch (const std::runtime_error &) { failed = true; }
	independent_value::reject_copy = false;
	require(failed && values.at(scpp::string_t{"key"}).number == 1, "failed replacement leaves old value");
	scpp::hash_t<scpp::mixed_t> wrapped;
	auto &same = wrapped.set(scpp::string_t{"n"}, scpp::mixed_t{scpp::int_t<>{3LL}});
	static_assert(std::is_same_v<decltype(same), scpp::hash_t<scpp::mixed_t> &>);
	require(&same == &wrapped, "fluent set keeps wrapper identity");
	require(wrapped._find_val(scpp::string_t{"n"}).int_value().native_value() == 3, "legacy read uses common engine");
	require(wrapped._find_val(scpp::string_t{"missing"}).is_null().native_value(), "legacy miss stays null");
}

} // namespace

int main() {
	shape_keys_and_lookup();
	growth_removal_iteration_and_copy();
	copy_failure_and_wrapper_surface();
}
