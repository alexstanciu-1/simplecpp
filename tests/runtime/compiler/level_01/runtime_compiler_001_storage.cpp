#include "storage_test_support.hpp"

static_assert(!detail::storage_integer<bool>);
static_assert(!detail::storage_integer<double>);
static_assert(!detail::storage_integer<std::string>);
static_assert(!std::is_constructible_v<Storage<record>, bool>);
static_assert(!std::is_constructible_v<Storage<record>, double>);

int main() {
	rejects<std::invalid_argument>([] { Storage<record> invalid(-1); });
	Storage<record> list(8);
	assert(list.is_empty());
	auto a = scpp::create<record>(10);
	auto b = scpp::create<record>(20);
	assert(list.append(a) == 0 && list.append(b) == 1 && list.append(a) == 2);
	list.remove(1);
	assert(list.count() == 2 && !list.contains(1) && !list.contains(-1));
	rejects<std::out_of_range>([&] { (void)list.read(1); });
	rejects<std::out_of_range>([&] { list.replace(1, b); });
	rejects<std::out_of_range>([&] { list.remove(-1); });
	rejects<std::out_of_range>([&] { (void)list.read(99); });
	list.unset(-1); list.unset(99); list.unset(1);
	rejects<std::invalid_argument>([&] { list.append({}); });
	rejects<std::invalid_argument>([&] { list.replace(0, {}); });
	rejects<std::invalid_argument>([&] { list.reserve(-2); });
	assert(list.count() == 2 && list.read(0).get() == a.get());
	assert(list.append(b) == 3);
	std::vector<std::int64_t> keys;
	list.for_each([&](auto key, auto value) { keys.push_back(key); ++value->value; });
	assert((keys == std::vector<std::int64_t>{0, 2, 3}));
	assert(a->value == 12 && b->value == 21);
	list.reserve(2048); list.reserve(0);
	assert(list.count() == 3 && list.read(2).get() == a.get());
	for (int i = 0; i < 3000; ++i) assert(list.append(a) == i + 4);
	assert(list.count() == 3003);
	const auto max = std::numeric_limits<std::int64_t>::max();
	assert(detail::append_position(max - 1) == max - 1);
	rejects<std::overflow_error>([&] { (void)detail::append_position(max); });
	rejects<std::overflow_error>([&] { (void)detail::append_position(std::uintmax_t(max) + 1); });

	Storage<record> reserved(4);
	allocations_until_failure = 0;
	assert(reserved.append(a) == 0);
	reserved.replace(0, b);
	allocations_until_failure = -1;
	reserved.remove(0);
	assert(reserved.is_empty() && reserved.append(a) == 1);

	allocation_sweep([&](long n) {
		Storage<record> trial;
		allocations_until_failure = n;
		bool failed = false;
		try { trial.append(a); } catch (const std::bad_alloc &) { failed = true; }
		allocations_until_failure = -1;
		assert(trial.count() == (failed ? 0u : 1u));
		assert(trial.append(b) == (failed ? 0 : 1));
		return !failed;
	});
	allocation_sweep([&](long n) {
		Storage<record> trial;
		trial.append(a);
		allocations_until_failure = n;
		bool failed = false;
		try { trial.reserve(4096); } catch (const std::bad_alloc &) { failed = true; }
		allocations_until_failure = -1;
		assert(trial.count() == 1 && trial.read(0).get() == a.get());
		assert(trial.append(b) == 1);
		return !failed;
	});
}
