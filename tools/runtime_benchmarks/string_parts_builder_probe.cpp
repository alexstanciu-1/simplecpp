#include "scpp/runtime.hpp"

#include <chrono>
#include <cstdint>
#include <iostream>
#include <string>

namespace {

using clock_t = std::chrono::steady_clock;

[[nodiscard]] std::uint64_t elapsed_us(clock_t::time_point start, clock_t::time_point end) {
	return static_cast<std::uint64_t>(std::chrono::duration_cast<std::chrono::microseconds>(end - start).count());
}

[[nodiscard]] std::uint32_t parse_arg(int argc, char **argv, int index, std::uint32_t fallback) {
	if (argc <= index) {
		return fallback;
	}
	const auto value = std::stoul(argv[index]);
	return static_cast<std::uint32_t>(value > 0U ? value : fallback);
}

[[nodiscard]] std::uint64_t checksum(const std::string &text) {
	std::uint64_t sum = 0;
	for (const unsigned char ch : text) {
		sum += static_cast<std::uint64_t>(ch);
	}
	return sum;
}

[[nodiscard]] scpp::string_t build_json_with_builder(const std::uint32_t rows) {
	auto builder = scpp::str::string_parts_builder_create();
	scpp::str::string_parts_builder_reserve(builder, scpp::int_t<>(static_cast<std::int64_t>(rows * 8U + 2U)));
	scpp::str::string_parts_builder_append_string(builder, scpp::string_t("["));
	for (std::uint32_t i = 0; i < rows; ++i) {
		if (i > 0U) {
			scpp::str::string_parts_builder_append_string(builder, scpp::string_t(","));
		}
		scpp::str::string_parts_builder_append_string(builder, scpp::string_t("{\"id\":"));
		scpp::str::string_parts_builder_append_int(builder, scpp::int_t<>(static_cast<std::int64_t>(i)));
		scpp::str::string_parts_builder_append_string(builder, scpp::string_t(",\"dirty\":"));
		scpp::str::string_parts_builder_append_bool(builder, scpp::bool_t((i % 3U) == 0U));
		scpp::str::string_parts_builder_append_string(builder, scpp::string_t("}"));
	}
	scpp::str::string_parts_builder_append_string(builder, scpp::string_t("]"));
	return scpp::str::string_parts_builder_to_string(builder);
}

[[nodiscard]] std::string build_json_with_direct_string(const std::uint32_t rows) {
	std::string out;
	out.reserve(static_cast<std::size_t>(rows) * 24U);
	out += "[";
	for (std::uint32_t i = 0; i < rows; ++i) {
		if (i > 0U) {
			out += ",";
		}
		out += "{\"id\":";
		out += std::to_string(i);
		out += ",\"dirty\":";
		out += ((i % 3U) == 0U ? "1" : "");
		out += "}";
	}
	out += "]";
	return out;
}

void run_builder_probe(const std::uint32_t rows, const std::uint32_t iterations) {
	scpp::string_t last;
	const auto start = clock_t::now();
	for (std::uint32_t i = 0; i < iterations; ++i) {
		last = build_json_with_builder(rows);
	}
	const auto total_us = elapsed_us(start, clock_t::now());
	std::cout
		<< "string_parts_builder_json"
		<< " rows=" << rows
		<< " iterations=" << iterations
		<< " total_us=" << total_us
		<< " avg_us=" << (total_us / iterations)
		<< " bytes=" << last.native_value().size()
		<< " checksum=" << checksum(last.native_value())
		<< "\n";
}

void run_direct_probe(const std::uint32_t rows, const std::uint32_t iterations) {
	std::string last;
	const auto start = clock_t::now();
	for (std::uint32_t i = 0; i < iterations; ++i) {
		last = build_json_with_direct_string(rows);
	}
	const auto total_us = elapsed_us(start, clock_t::now());
	std::cout
		<< "direct_string_json"
		<< " rows=" << rows
		<< " iterations=" << iterations
		<< " total_us=" << total_us
		<< " avg_us=" << (total_us / iterations)
		<< " bytes=" << last.size()
		<< " checksum=" << checksum(last)
		<< "\n";
}

} // namespace

int main(int argc, char **argv) {
	const auto rows = parse_arg(argc, argv, 1, 10000U);
	const auto iterations = parse_arg(argc, argv, 2, 100U);
	run_builder_probe(rows, iterations);
	run_direct_probe(rows, iterations);
	return 0;
}
