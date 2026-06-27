#include "scpp/hash_t.hpp"

#include <chrono>
#include <cstdint>
#include <iostream>
#include <string>
#include <vector>

namespace {

using clock_t = std::chrono::steady_clock;

[[nodiscard]] std::uint64_t elapsed_us(clock_t::time_point start, clock_t::time_point end) {
	return static_cast<std::uint64_t>(std::chrono::duration_cast<std::chrono::microseconds>(end - start).count());
}

[[nodiscard]] std::uint32_t parse_count(int argc, char **argv) {
	if (argc < 2) {
		return 100000U;
	}
	const auto value = std::stoul(argv[1]);
	return static_cast<std::uint32_t>(value > 0U ? value : 100000U);
}

void run_string_key_probe(std::uint32_t count) {
	std::vector<scpp::string_t> keys;
	keys.reserve(count);
	for (std::uint32_t i = 0; i < count; ++i) {
		keys.emplace_back(std::string("key_") + std::to_string(i));
	}

	scpp::hash_t<scpp::int_t<>> table;
	const auto insert_start = clock_t::now();
	for (std::uint32_t i = 0; i < count; ++i) {
		table.set(keys[i], scpp::int_t<>(static_cast<std::int64_t>(i)));
	}
	const auto insert_end = clock_t::now();

	std::int64_t sum = 0;
	const auto lookup_start = clock_t::now();
	for (std::uint32_t i = 0; i < count; ++i) {
		sum += table.at(keys[i]).native_value();
	}
	const auto lookup_end = clock_t::now();

	std::cout
		<< "hash_string_key"
		<< " count=" << count
		<< " insert_us=" << elapsed_us(insert_start, insert_end)
		<< " lookup_us=" << elapsed_us(lookup_start, lookup_end)
		<< " checksum=" << sum
		<< "\n";
}

void run_int_key_probe(std::uint32_t count) {
	scpp::hash_t<scpp::int_t<>, scpp::int_t<>> table;
	const auto insert_start = clock_t::now();
	for (std::uint32_t i = 0; i < count; ++i) {
		table.set(scpp::int_t<>(static_cast<std::int64_t>(i)), scpp::int_t<>(static_cast<std::int64_t>(i)));
	}
	const auto insert_end = clock_t::now();

	std::int64_t sum = 0;
	const auto lookup_start = clock_t::now();
	for (std::uint32_t i = 0; i < count; ++i) {
		sum += table.at(scpp::int_t<>(static_cast<std::int64_t>(i))).native_value();
	}
	const auto lookup_end = clock_t::now();

	std::cout
		<< "hash_int_key"
		<< " count=" << count
		<< " insert_us=" << elapsed_us(insert_start, insert_end)
		<< " lookup_us=" << elapsed_us(lookup_start, lookup_end)
		<< " checksum=" << sum
		<< "\n";
}

void run_uint32_key_probe(std::uint32_t count) {
	scpp::hash_t<scpp::int_t<>, scpp::int_t<std::uint32_t>> table;
	const auto insert_start = clock_t::now();
	for (std::uint32_t i = 0; i < count; ++i) {
		table.set(scpp::int_t<std::uint32_t>(i), scpp::int_t<>(static_cast<std::int64_t>(i)));
	}
	const auto insert_end = clock_t::now();

	std::int64_t sum = 0;
	const auto lookup_start = clock_t::now();
	for (std::uint32_t i = 0; i < count; ++i) {
		sum += table.at(scpp::int_t<std::uint32_t>(i)).native_value();
	}
	const auto lookup_end = clock_t::now();

	std::cout
		<< "hash_uint32_key"
		<< " count=" << count
		<< " insert_us=" << elapsed_us(insert_start, insert_end)
		<< " lookup_us=" << elapsed_us(lookup_start, lookup_end)
		<< " checksum=" << sum
		<< "\n";
}

} // namespace

int main(int argc, char **argv) {
	const auto count = parse_count(argc, argv);
	run_string_key_probe(count);
	run_int_key_probe(count);
	run_uint32_key_probe(count);
	return 0;
}
