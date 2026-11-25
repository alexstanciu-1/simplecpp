#include "scpp/value_iteration.hpp"
#include <barrier>
#include <cassert>
#include <thread>
#include <vector>

// Independent readers share the same const wrapper, including its first lazy
// iteration-source publication. Writers run only after the reader join.
int main() {
	scpp::vector_t<int> values{1, 2, 3, 4};
	scpp::hash_t<int, scpp::int_t<>> table;
	for (int i = 0; i < 4; ++i) table[scpp::int_t<>(i)] = i + 1;
	std::barrier start(16);
	std::vector<std::jthread> readers;
	for (int worker = 0; worker < 16; ++worker) readers.emplace_back([&] {
		start.arrive_and_wait();
		for (int repeat = 0; repeat < 2000; ++repeat) {
			int sum = 0;
			for (auto item : scpp::value_iteration(values)) sum += item.value_copy();
			assert(sum == 10);
			sum = 0;
			for (auto item : scpp::value_iteration(table)) sum += item.value_copy();
			assert(sum == 10);
		}
	});
	readers.clear();
	// Existing mutation preservation still applies after concurrency ends.
	int sum = 0;
	for (auto item : scpp::value_iteration(values)) {
		sum += item.value_copy();
		values.clear();
	}
	assert(sum == 10);
	sum = 0;
	for (auto item : scpp::value_iteration(table)) {
		sum += item.value_copy();
		table.clear();
	}
	assert(sum == 10);
}
