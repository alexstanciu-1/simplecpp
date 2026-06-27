#include "modules/tokenizer/tokenizer.hpp"

#include <chrono>
#include <cstdint>
#include <iomanip>
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

[[nodiscard]] std::string make_phs_source(const std::uint32_t target_bytes) {
	std::string source;
	source.reserve(target_bytes + 128U);
	for (std::uint32_t i = 0; source.size() < target_bytes; ++i) {
		source += "function f";
		source += std::to_string(i);
		source += "(int $a, int $b): int {\n";
		source += "    return $a + $b + ";
		source += std::to_string(i % 17U);
		source += ";\n}\n";
	}
	return source;
}

[[nodiscard]] std::string make_jss_source(const std::uint32_t target_bytes) {
	std::string source;
	source.reserve(target_bytes + 128U);
	for (std::uint32_t i = 0; source.size() < target_bytes; ++i) {
		source += "function f";
		source += std::to_string(i);
		source += "(a, b) {\n";
		source += "    items.push(a + b + ";
		source += std::to_string(i % 17U);
		source += ");\n}\n";
	}
	return source;
}

[[nodiscard]] std::uint64_t estimated_token_buffer_bytes(const scpp::tokenizer::token_buffer &buffer) {
	return static_cast<std::uint64_t>(sizeof(scpp::tokenizer::token_buffer))
		+ static_cast<std::uint64_t>(buffer.kind_ids.capacity() * sizeof(std::uint8_t))
		+ static_cast<std::uint64_t>(buffer.start_offsets.capacity() * sizeof(std::uint32_t))
		+ static_cast<std::uint64_t>(buffer.lengths.capacity() * sizeof(std::uint32_t))
		+ static_cast<std::uint64_t>(buffer.line_numbers.capacity() * sizeof(std::uint32_t))
		+ static_cast<std::uint64_t>(buffer.columns.capacity() * sizeof(std::uint32_t))
		+ static_cast<std::uint64_t>(buffer.flags.capacity() * sizeof(std::uint16_t))
		+ static_cast<std::uint64_t>(buffer.line_start_offsets.capacity() * sizeof(std::uint32_t))
		+ static_cast<std::uint64_t>(buffer.diagnostics.capacity() * sizeof(scpp::tokenizer::token_diagnostic));
}

[[nodiscard]] std::uint64_t checksum(const scpp::tokenizer::token_buffer &buffer) {
	std::uint64_t sum = static_cast<std::uint64_t>(buffer.token_count());
	for (std::size_t i = 0; i < buffer.kind_ids.size(); ++i) {
		sum += static_cast<std::uint64_t>(buffer.kind_ids[i]);
		sum += static_cast<std::uint64_t>(buffer.start_offsets[i]);
		sum += static_cast<std::uint64_t>(buffer.lengths[i]);
	}
	return sum;
}

void run_probe(const char *label, const std::string &source, const std::uint32_t iterations) {
	const scpp::string_t text(source);
	const bool is_phs = std::string(label) == "phs";
	scpp::tokenizer::token_buffer_t last;
	const auto start = clock_t::now();
	for (std::uint32_t i = 0; i < iterations; ++i) {
		if (is_phs) {
			last = scpp::tokenizer::phs_tokenize_buffer(text);
		} else {
			last = scpp::tokenizer::jss_tokenize_buffer(text);
		}
	}
	const auto end = clock_t::now();
	const auto total_us = elapsed_us(start, end);
	const auto memory_bytes = estimated_token_buffer_bytes(*last);
	std::cout
		<< "tokenizer_" << label
		<< " source_bytes=" << source.size()
		<< " iterations=" << iterations
		<< " total_us=" << total_us
		<< " avg_us=" << (total_us / iterations)
		<< " token_count=" << last->token_count()
		<< " estimated_buffer_bytes=" << memory_bytes
		<< " bytes_per_source_byte=" << std::fixed << std::setprecision(3)
		<< (static_cast<double>(memory_bytes) / static_cast<double>(source.size()))
		<< " checksum=" << checksum(*last)
		<< "\n";
}

} // namespace

int main(int argc, char **argv) {
	const auto target_bytes = parse_arg(argc, argv, 1, 100000U);
	const auto iterations = parse_arg(argc, argv, 2, 100U);
	run_probe("phs", make_phs_source(target_bytes), iterations);
	run_probe("jss", make_jss_source(target_bytes), iterations);
	return 0;
}
