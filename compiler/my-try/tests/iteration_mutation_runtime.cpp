#include "scpp/value_iteration.hpp"
#include <iostream>
#include <cassert>
#include <memory>
#include <stdexcept>
#include <vector>

struct copy_probe {
	static inline int copies_left = -1;
	int value;
	explicit copy_probe(int input = 0) : value(input) {}
	copy_probe(const copy_probe &other) : value(other.value) {
		if (copies_left == 0) throw std::runtime_error("preservation copy failed");
		if (copies_left > 0) --copies_left;
	}
	copy_probe(copy_probe &&) noexcept = default;
	copy_probe &operator=(const copy_probe &) = default;
	copy_probe &operator=(copy_probe &&) noexcept = default;
};

// Failure after the inner traversal prepares must roll back both traversals.
void prove_failed_preservation() {
	scpp::vector_t<copy_probe> source{copy_probe{1}, copy_probe{2}, copy_probe{3}};
	auto outer = scpp::value_iteration(source);
	auto inner = scpp::value_iteration(source);
	auto left = outer.begin();
	auto right = inner.begin();
	assert((*left).value_copy().value == 1);
	assert((*right).value_copy().value == 1);
	copy_probe::copies_left = 2;
	bool threw = false;
	try { source.append(copy_probe{4}); }
	catch (const std::runtime_error &) { threw = true; }
	copy_probe::copies_left = -1;
	assert(threw && source.size() == 3);
	source.at(1).value = 20;
	++left;
	++right;
	assert((*left).value_copy().value == 20);
	assert((*right).value_copy().value == 20);
	source.append(copy_probe{4});
	source.at(2).value = 30;
	++left;
	++right;
	assert((*left).value_copy().value == 3);
	assert((*right).value_copy().value == 3);
}

void prove_lifetime_and_moves() {
	using integer = scpp::int_t<>;
	auto owner = std::make_unique<scpp::vector_t<integer>>(
		std::initializer_list<integer>{integer{1}, integer{2}, integer{3}});
	std::int64_t total = 0;
	for (auto entry : scpp::value_iteration(*owner)) {
		total += entry.value_copy().native_value();
		owner.reset();
	}
	assert(total == 6);
	scpp::vector_t<integer> source{integer{1}, integer{2}, integer{3}};
	scpp::vector_t<integer> target{integer{7}, integer{8}, integer{9}};
	auto source_range = scpp::value_iteration(source);
	auto target_range = scpp::value_iteration(target);
	auto left = source_range.begin();
	auto right = target_range.begin();
	assert((*left).value_copy().native_value() == 1);
	assert((*right).value_copy().native_value() == 7);
	target = std::move(source);
	++left;
	++right;
	assert((*left).value_copy().native_value() == 2);
	assert((*right).value_copy().native_value() == 8);
	assert(target.at(0).native_value() == 1);
}

void prove_nonstructural_and_nested() {
	using integer = scpp::int_t<>;
	scpp::vector_t<integer> source{integer{1}, integer{2}, integer{3}};
	std::vector<std::int64_t> actual;
	for (auto entry : scpp::value_iteration(source)) {
		auto value = entry.value_copy().native_value();
		actual.push_back(value);
		if (value == 1) {
			source.reserve(100);
			source.compact();
			source.resize(3, integer{0});
			assert(!source.remove(integer{-1}));
			source.at(1) = integer{20};
		}
	}
	assert((actual == std::vector<std::int64_t>{1, 20, 3}));
	actual.clear();
	for (auto outer : scpp::value_iteration(source)) {
		auto value = outer.value_copy().native_value();
		actual.push_back(value);
		if (value == 1) {
			source.append(integer{4});
			source.at(1) = integer{200};
			std::vector<std::int64_t> nested;
			for (auto inner : scpp::value_iteration(source)) nested.push_back(inner.value_copy().native_value());
			assert((nested == std::vector<std::int64_t>{1, 200, 3, 4}));
		}
	}
	assert((actual == std::vector<std::int64_t>{1, 20, 3}));
}

void prove_structural_operations() {
	using integer = scpp::int_t<>;
	for (int operation = 0; operation < 5; ++operation) {
		scpp::vector_t<integer> source{integer{1}, integer{2}, integer{3}};
		std::vector<std::int64_t> actual;
		for (auto entry : scpp::value_iteration(source)) {
			auto value = entry.value_copy().native_value();
			actual.push_back(value);
			if (value != 1) continue;
			switch (operation) {
			case 0: assert(source.remove(integer{0})); break;
			case 1: source.clear(); break;
			case 2: source.resize(1, integer{0}); break;
			case 3: source.resize(5, integer{0}); break;
			case 4: source._unset_(); break;
			}
		}
		assert((actual == std::vector<std::int64_t>{1, 2, 3}));
	}
}

void prove_hash_preservation() {
	using integer = scpp::int_t<>;
	using table = scpp::hash_t<integer, integer>;
	for (int operation = 0; operation < 4; ++operation) {
		table source;
		source.set(integer{10}, integer{1});
		source.set(integer{15}, integer{99});
		source.set(integer{20}, integer{2});
		source.set(integer{30}, integer{3});
		assert(source.remove(integer{15}));
		std::vector<std::int64_t> actual;
		for (auto entry : scpp::value_iteration(source)) {
			actual.push_back(entry.key().native_value());
			auto value = entry.value_copy().native_value();
			actual.push_back(value);
			if (value != 1) continue;
			source.set(integer{20}, integer{200});
			switch (operation) {
			case 0: source.set(integer{40}, integer{4}); break;
			case 1: assert(source.remove(integer{30})); break;
			case 2: source.clear(); break;
			case 3: source = table{}; break;
			}
			if (operation < 2) source.set(integer{20}, integer{999});
		}
		assert((actual == std::vector<std::int64_t>{10, 1, 20, 200, 30, 3}));
	}
	auto owner = std::make_unique<table>();
	owner->set(integer{10}, integer{1});
	owner->set(integer{20}, integer{2});
	int total = 0;
	for (auto entry : scpp::value_iteration(*owner)) {
		total += entry.value_copy().native_value();
		owner.reset();
	}
	assert(total == 3);

	scpp::hash_t<copy_probe, integer> source;
	source.set(integer{10}, copy_probe{1});
	source.set(integer{20}, copy_probe{2});
	source.set(integer{30}, copy_probe{3});
	auto outer = scpp::value_iteration(source);
	auto inner = scpp::value_iteration(source);
	auto left = outer.begin();
	auto right = inner.begin();
	assert((*left).value_copy().value == 1);
	assert((*right).value_copy().value == 1);
	copy_probe::copies_left = 2;
	bool threw = false;
	try { source.set(integer{40}, copy_probe{4}); }
	catch (const std::runtime_error &) { threw = true; }
	copy_probe::copies_left = -1;
	assert(threw && source.size() == 3);
	source.at(integer{20}).value = 200;
	++left;
	++right;
	assert((*left).value_copy().value == 200);
	assert((*right).value_copy().value == 200);
}

std::int64_t read_integer(const scpp::mixed_t &value) { return value.int_value().native_value(); }
std::int64_t read_integer(const scpp::detail::dynamic_value &value) { return value.get_int().native_value(); }

template<class Value>
void prove_packed_preservation() {
	using integer = scpp::int_t<>;
	using table = scpp::hash_detail::packed_table<Value>;
	for (int operation = 0; operation < 5; ++operation) {
		table source;
		(void)source.append(Value{integer{1}});
		(void)source.append(Value{integer{2}});
		(void)source.append(Value{integer{3}});
		std::vector<std::int64_t> actual;
		for (auto entry : source.value_range()) {
			actual.push_back(read_integer(entry.key()));
			auto value = read_integer(entry.value_copy());
			actual.push_back(value);
			if (value != 1) continue;
			source.set(integer{1}, Value{integer{20}});
			switch (operation) {
			case 0: (void)source.append(Value{integer{4}}); break;
			case 1: source.set(scpp::string_t{"new"}, Value{integer{4}}); break;
			case 2: assert(source.remove(integer{1})); break;
			case 3: source.clear(); break;
			case 4: source = table{}; break;
			}
			if (operation < 3) source.set(integer{2}, Value{integer{30}});
		}
		assert((actual == std::vector<std::int64_t>{0, 1, 1, 20, 2, 3}));
	}
	auto source = std::make_unique<table>();
	(void)source->append(Value{integer{1}});
	(void)source->append(Value{integer{2}});
	int sum = 0;
	for (auto entry : source->value_range()) {
		sum += read_integer(entry.value_copy());
		source.reset();
	}
	assert(sum == 3);

	table associative;
	associative.set(scpp::string_t{"a"}, Value{integer{1}});
	associative.set(scpp::string_t{"gone"}, Value{integer{9}});
	associative.set(scpp::string_t{"b"}, Value{integer{2}});
	assert(associative.remove(scpp::string_t{"gone"}));
	std::string keys;
	sum = 0;
	for (auto entry : associative.value_range()) {
		keys += static_cast<scpp::string_t>(entry.key()).native_value();
		auto value = read_integer(entry.value_copy());
		sum += value;
		if (value == 1) {
			associative.set(scpp::string_t{"c"}, Value{integer{3}});
			associative.set(scpp::string_t{"b"}, Value{integer{200}});
		}
	}
	assert(keys == "ab" && sum == 3);
}

// Runtime-first, bounds-safe probes. Do not exercise dangling table iterators
// until the source-lifetime protocol exists.
int main(int argc, char **) {
	using integer = scpp::int_t<>;
	scpp::vector_t<integer> live{integer{1}, integer{2}, integer{3}};
	std::cout << "live:";
	for (auto entry : scpp::value_iteration(live)) {
		auto value = entry.value_copy();
		std::cout << ' ' << value.native_value();
		if (value.native_value() == 1) live.at(1) = integer{20};
	}
	std::cout << '\n';
	scpp::vector_t<integer> source{integer{1}, integer{2}, integer{3}};
	std::vector<std::int64_t> visited;
	for (auto entry : scpp::value_iteration(source)) {
		auto value = entry.value_copy();
		visited.push_back(value.native_value());
		if (value.native_value() == 1) {
			source.at(1) = integer{20};
			source.append(integer{4});
			source.at(2) = integer{30};
		}
	}
	std::cout << "append:";
	for (auto value : visited) std::cout << ' ' << value;
	std::cout << '\n';

	scpp::vector_t<integer> replacement{integer{1}, integer{2}, integer{3}};
	std::cout << "replacement:";
	try {
		for (auto entry : scpp::value_iteration(replacement)) {
			auto value = entry.value_copy();
			std::cout << ' ' << value.native_value();
			if (value.native_value() == 1)
				replacement = scpp::vector_t<integer>{integer{9}};
		}
	} catch (const std::exception &) {
		std::cout << " threw";
	}
	std::cout << '\n';
	if (argc > 1) return 0; // Historical observation mode excludes acceptance assertions.
	prove_failed_preservation();
	prove_lifetime_and_moves();
	prove_nonstructural_and_nested();
	prove_structural_operations();
	prove_hash_preservation();
	prove_packed_preservation<scpp::mixed_t>();
	prove_packed_preservation<scpp::detail::dynamic_value>();
}
