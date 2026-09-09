#include "scpp/runtime.hpp"
#include "operators/memory_usage/memory_usage.hpp"

#include <cstdint>
#include <iostream>
#include <string>

namespace {

[[nodiscard]] std::uint32_t parse_arg(int argc, char **argv, int index, std::uint32_t fallback) {
	if (argc <= index) {
		return fallback;
	}
	const auto value = std::stoul(argv[index]);
	return static_cast<std::uint32_t>(value > 0U ? value : fallback);
}

} // namespace

int main(int argc, char **argv) {
	const auto count = parse_arg(argc, argv, 1, 10000U);

	std::string native_text;
	native_text.reserve(count);
	for (std::uint32_t i = 0; i < count; ++i) {
		native_text.push_back(static_cast<char>('a' + (i % 26U)));
	}
	const scpp::string_t text(native_text);

	scpp::vector_t<scpp::int_t<>> vector;
	vector.reserve(count);
	for (std::uint32_t i = 0; i < count; ++i) {
		vector.append(scpp::int_t<>(static_cast<std::int64_t>(i)));
	}

	scpp::hash_t<scpp::int_t<>, scpp::int_t<std::uint32_t>> hash;
	for (std::uint32_t i = 0; i < count; ++i) {
		hash[scpp::int_t<std::uint32_t>(i)] = scpp::int_t<>(static_cast<std::int64_t>(i));
	}

	std::cout
		<< "memory_accounting_string"
		<< " bytes=" << scpp::memory::string_byte_length(text)
		<< " capacity=" << scpp::memory::string_byte_capacity(text)
		<< " estimated_bytes=" << scpp::memory::estimated_bytes(text)
		<< "\n";

	std::cout
		<< "memory_accounting_vector"
		<< " count=" << scpp::memory::vector_count(vector)
		<< " capacity=" << scpp::memory::vector_capacity(vector)
		<< " estimated_bytes=" << scpp::memory::estimated_bytes(vector)
		<< "\n";

	std::cout
		<< "memory_accounting_hash"
		<< " count=" << scpp::memory::hash_count(hash)
		<< " value_capacity=" << scpp::memory::hash_capacity(hash)
		<< " key_capacity=" << scpp::memory::hash_key_capacity(hash)
		<< " index_capacity=" << scpp::memory::hash_index_capacity(hash)
		<< " estimated_bytes=" << scpp::memory::estimated_bytes(hash)
		<< "\n";

	std::cout
		<< "memory_accounting_process"
		<< " rss_bytes=" << scpp::memory_get_usage().native_value()
		<< " peak_rss_bytes=" << scpp::memory_get_peak_usage().native_value()
		<< "\n";

	return 0;
}
