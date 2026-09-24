#include "storage_test_support.hpp"

static_assert(!detail::storage_string<int>);
static_assert(!detail::storage_string<bool>);
static_assert(!detail::storage_string<std::nullptr_t>);
static_assert(!std::is_constructible_v<Keyed_Storage<record>, double>);

int main() {
	rejects<std::invalid_argument>([] { Keyed_Storage<record> invalid(-1); });
	Keyed_Storage<record> map(16);
	auto a = scpp::create<record>(1);
	auto b = scpp::create<record>(2);
	const std::string nul("a\0b", 3);
	const std::vector<std::string> input{"", "0", "00", "1", "01", nul, "a"};
	for (const auto &key : input) map.add(key, a);
	assert(map.count() == input.size());
	for (const auto &key : input) assert(map.read(key).get() == a.get());
	rejects<std::invalid_argument>([&] { map.add("0", b); });
	rejects<std::invalid_argument>([&] { map.add("new", {}); });
	rejects<std::invalid_argument>([&] { map.set("0", {}); });
	rejects<std::invalid_argument>([&] { map.set("new", {}); });
	rejects<std::invalid_argument>([&] { map.replace("0", {}); });
	rejects<std::invalid_argument>([&] { map.reserve(-1); });
	rejects<std::out_of_range>([&] { map.replace("new", b); });
	rejects<std::out_of_range>([&] { map.remove("new"); });
	rejects<std::out_of_range>([&] { (void)map.read("new"); });
	assert(!map.contains("new") && map.count() == input.size());
	map.unset("new");
	map.set("0", b);
	map.replace("a", b);
	std::vector<std::string> keys;
	map.for_each([&](auto key, auto value) { keys.push_back(key); ++value->value; });
	assert(keys == input && b->value == 4);
	map.remove("0"); map.set("0", a);
	keys.clear();
	map.for_each([&](auto key, auto) { keys.push_back(key); });
	assert((keys == std::vector<std::string>{"", "00", "1", "01", nul, "a", "0"}));
	map.reserve(8192); map.reserve(0);
	for (int i = 0; i < 3000; ++i) { map.set("churn", b); map.unset("churn"); }
	assert(map.count() == input.size() && map.read(nul).get() == a.get());
	for (const auto &key : input) map.remove(key);
	assert(map.is_empty());

	for (bool use_set : {false, true}) allocation_sweep([&](long n) {
		Keyed_Storage<record> trial;
		trial.reserve(1);
		trial.add("existing", a);
		trial.add("second", a);
		const std::string key(100, 'x'); // Exercise both owned key copies, nodes and rehash.
		allocations_until_failure = n;
		bool failed = false;
		try { if (use_set) trial.set(key, b); else trial.add(key, b); }
		catch (const std::bad_alloc &) { failed = true; }
		allocations_until_failure = -1;
		assert(trial.count() == (failed ? 2u : 3u));
		assert(trial.contains(key) == !failed && trial.read("existing").get() == a.get());
		std::size_t seen = 0;
		trial.for_each([&](auto, auto) { ++seen; });
		assert(seen == trial.count());
		trial.set(key, b); trial.remove("existing");
		assert(trial.count() == 2 && trial.read(key).get() == b.get());
		return !failed;
	});
	allocation_sweep([&](long n) {
		Keyed_Storage<record> trial;
		trial.add("a", a);
		allocations_until_failure = n;
		bool failed = false;
		try { trial.reserve(8192); } catch (const std::bad_alloc &) { failed = true; }
		allocations_until_failure = -1;
		assert(trial.count() == 1 && trial.read("a").get() == a.get());
		trial.add("b", b);
		return !failed;
	});
}
