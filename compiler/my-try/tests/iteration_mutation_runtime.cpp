#include "scpp/value_iteration.hpp"
#include <iostream>
#include <cassert>
#include <memory>
#include <stdexcept>
#include <vector>

struct copy_probe {
	static inline int copies_left = -1;
	int value;
	explicit copy_probe(int input) : value(input) {}
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
}
