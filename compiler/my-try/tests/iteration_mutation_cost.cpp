#include "scpp/value_iteration.hpp"
#include <algorithm>
#include <array>
#include <atomic>
#include <cassert>
#include <chrono>
#include <iostream>
#include <type_traits>

using integer = scpp::int_t<>;

struct counted {
	static inline std::size_t copies = 0;
	counted() = default;
	counted(const counted &) { ++copies; }
	counted(counted &&) noexcept = default;
	counted &operator=(const counted &) = default;
	counted &operator=(counted &&) noexcept = default;
};

void prove_copy_cost() {
	scpp::vector_t<counted> source;
	for (int i = 0; i < 1024; ++i) source.append(counted{});
	counted::copies = 0;
	auto range = scpp::value_iteration(source);
	assert(counted::copies == 0);
	auto cursor = range.begin();
	(void)(*cursor).value_copy();
	assert(counted::copies == 1);
	source.append(counted{});
	assert(counted::copies == 1024);
	source.append(counted{});
	assert(counted::copies == 1024);
	std::cout << "copies: entry 0, current 1, first mutation 1023, subsequent mutation 0\n";
}

// Compare registration/traversal against the prior borrowed vector path in the
// same optimized binary. This is not a whole-runtime or compiler benchmark.
template<bool Preserved>
double measure(scpp::vector_t<integer> &source, int rounds) {
	std::array<double, 7> samples{};
	for (auto &sample : samples) {
		std::int64_t sum = 0;
		auto start = std::chrono::steady_clock::now();
		for (int round = 0; round < rounds; ++round) {
			std::atomic_signal_fence(std::memory_order_seq_cst);
			if constexpr (Preserved) {
				for (auto entry : scpp::value_iteration(source)) sum += entry.value_copy().native_value();
			} else {
				for (auto entry : scpp::foreach_range(source)) sum += entry.value_copy().native_value();
			}
		}
		auto end = std::chrono::steady_clock::now();
		if (sum != static_cast<std::int64_t>(source.size()) * rounds) throw std::runtime_error("checksum");
		sample = std::chrono::duration<double, std::nano>(end - start).count() / rounds;
	}
	std::sort(samples.begin(), samples.end());
	return samples[3];
}

int main() {
	prove_copy_cost();
	std::cout << "size vector<int> " << sizeof(scpp::vector_t<integer>)
		<< " hash<int> " << sizeof(scpp::hash_t<integer>)
		<< " mixed " << sizeof(scpp::mixed_t) << " dynamic " << sizeof(scpp::dynamic_t<>) << '\n';
	std::cout << "nothrow_move vector/hash/mixed/dynamic "
		<< std::is_nothrow_move_constructible_v<scpp::vector_t<integer>> << '/'
		<< std::is_nothrow_move_constructible_v<scpp::hash_t<integer>> << '/'
		<< std::is_nothrow_move_constructible_v<scpp::mixed_t> << '/'
		<< std::is_nothrow_move_constructible_v<scpp::dynamic_t<>> << '\n';
	for (auto size : {0, 1, 16, 1024, 65536}) {
		scpp::vector_t<integer> source;
		for (int i = 0; i < size; ++i) source.append(integer{1});
		int rounds = std::max(100, 2000000 / std::max(1, size));
		auto borrowed = measure<false>(source, rounds);
		auto preserved = measure<true>(source, rounds);
		std::cout << "entries " << size << " borrowed_ns/loop " << borrowed
			<< " preserved_ns/loop " << preserved << '\n';
	}
}
