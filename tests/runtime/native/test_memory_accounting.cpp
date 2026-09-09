#include "test_common.hpp"

#include "scpp/memory_accounting.hpp"

#include <cassert>

namespace {

void test_string_accounting() {
	scpp::string_t text("hello");
	const auto initial_capacity = scpp::memory::string_byte_capacity(text);
	assert(scpp::memory::string_byte_length(text) == 5U);
	assert(initial_capacity >= 5U);
	assert(scpp::memory::estimated_bytes(text) >= sizeof(scpp::string_t) + initial_capacity);
}

void test_vector_accounting() {
	scpp::vector_t<scpp::int_t<>> values;
	values.reserve(16);
	values.append(scpp::int_t<>(1));
	values.append(scpp::int_t<>(2));

	assert(scpp::memory::vector_count(values) == 2U);
	assert(scpp::memory::vector_capacity(values) >= 16U);
	assert(scpp::memory::estimated_bytes(values) >= sizeof(values) + values.capacity() * sizeof(scpp::int_t<>));
}

void test_typed_hash_accounting() {
	scpp::hash_t<scpp::int_t<>, scpp::int_t<std::uint32_t>> values;
	values[scpp::int_t<std::uint32_t>(1)] = scpp::int_t<>(10);
	values[scpp::int_t<std::uint32_t>(2)] = scpp::int_t<>(20);

	assert(scpp::memory::hash_count(values) == 2U);
	assert(scpp::memory::hash_capacity(values) >= 2U);
	assert(scpp::memory::hash_key_capacity(values) >= 2U);
	assert(scpp::memory::hash_index_capacity(values) >= 4U);
	assert(scpp::memory::estimated_bytes(values) >= sizeof(values));
}

} // namespace

int main() {
	test_string_accounting();
	test_vector_accounting();
	test_typed_hash_accounting();
	return 0;
}
