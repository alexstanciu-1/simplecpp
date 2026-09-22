#include "scpp/runtime.hpp"
#include <algorithm>
#include <chrono>
#include <iostream>

using namespace scpp;
using integer = int_t<>;
static volatile std::int64_t observed;

template <typename F>
void measure(const char *label, std::size_t count, F action) {
	std::vector<double> samples;
	for (int sample = 0; sample < 7; ++sample) {
		auto begin = std::chrono::steady_clock::now();
		for (int repeat = 0; repeat < 12; ++repeat) {
			auto output = action();
			std::int64_t sum = 0;
			for (const auto entry : foreach_range(output)) { sum += entry.value_copy().native_value(); }
			observed = sum;
		}
		auto end = std::chrono::steady_clock::now();
		samples.push_back(std::chrono::duration<double, std::nano>(end - begin).count() / (12 * count));
	}
	std::sort(samples.begin(), samples.end());
	std::cout << label << ',' << samples[3] << " ns/input element\n";
}

int main() {
	vector_t<integer> sequence;
	for (int i = 0; i < 100000; ++i) { sequence.append(integer(i)); }
	auto twice = [](integer n) -> integer { return n + n; };
	std::function<integer(integer)> stored = twice;
	measure("vector direct", sequence.size(), [&] {
		vector_t<integer> out; out.reserve(sequence.size());
		for (const auto entry : foreach_range(sequence)) { auto n = entry.value_copy(); out.append(n + n); }
		return out;
	});
	measure("vector inline", sequence.size(), [&] { return collections::map(sequence, twice); });
	measure("vector stored", sequence.size(), [&] { return collections::map(sequence, stored); });
	auto even = [](integer n) -> bool_t { return bool_t(n.native_value() % 2 == 0); };
	std::function<bool_t(integer)> stored_even = even;
	measure("filter direct", sequence.size(), [&] {
		vector_t<integer> out; out.reserve(sequence.size());
		for (const auto entry : foreach_range(sequence)) { auto n = entry.value_copy(); if (even(n).native_value()) out.append(n); }
		return out;
	});
	measure("filter inline", sequence.size(), [&] { return collections::filter(sequence, even); });
	measure("filter stored", sequence.size(), [&] { return collections::filter(sequence, stored_even); });
	hash_t<integer, integer> keyed;
	for (int i = 0; i < 10000; ++i) { keyed.set(integer(3 * i), integer(i)); }
	measure("hash direct", keyed.size(), [&] {
		hash_t<integer, integer> out;
		for (const auto entry : foreach_range(keyed)) { auto n = entry.value_copy(); out.set(entry.key(), n + n); }
		return out;
	});
	measure("hash inline", keyed.size(), [&] { return collections::map(keyed, twice); });
	measure("hash stored", keyed.size(), [&] { return collections::map(keyed, stored); });
}
