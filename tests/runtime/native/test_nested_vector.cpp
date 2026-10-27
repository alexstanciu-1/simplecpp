#include "public_api_guard.hpp"
#include "test_types.hpp"
#include <cstdio>
#include <cstdlib>
#include <new>

static unsigned allocations = 0;
void *operator new(std::size_t size)
{
	++allocations;
	if (auto memory = std::malloc(size ? size : 1)) return memory;
	throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }

using namespace scpp;
static void check(bool value, int line)
{
	if (!value) {
		std::fprintf(stderr, "nested vector check failed at %d\n", line);
		std::abort();
	}
}
#define CHECK(value) check(static_cast<bool>(value), __LINE__)

static vector_string row(const char *text)
{
	vector_string result;
	result.append(string(text));
	return result;
}

/** Return the nonmovable cursor directly; both original container levels die here. */
static auto surviving_outer()
{
	nested_vector values;
	values.append(row("long_first_value_that_requires_owned_storage"));
	values.append(row("second"));
	return values.iterate();
}

static auto surviving_inner()
{
	auto outer = surviving_outer();
	auto inner = outer.value();
	return inner.iterate();
}

static auto surviving_defaults()
{
	default_sequence inner;
	inner.append(string("default-owned"));
	default_nested outer;
	outer.append(inner);
	return outer.iterate();
}

int main()
{
    // Filled outer vectors own copies of their inner vectors, including after
    // the input or an extracted copy changes. The result-only fixture factories
    // have distinct names; ordinary vector uses its source helper spelling.
    {
        auto inner = row("nested fill");
        auto filled = vector_filled(int64(3), inner);
        inner.set(uint64(0), string("changed input"));
        CHECK(filled.at(uint64(0)).at(uint64(0)).equals("nested fill"));
        auto extracted = filled.at(uint64(1));
        extracted.clear();
        CHECK(filled.at(uint64(1)).size() == uint64(1));
        auto sequence = sequence_string_filled(int64(2), string("fixture fill"));
        auto defaults = nested_strings_filled(int64(2), sequence);
        sequence.clear();
        CHECK(defaults.at(uint64(0)).at(uint64(1)).equals("fixture fill"));
        const auto before = allocations;
        for (int i = 0; i < 100; ++i) {
            CHECK(count(filled) == uint64(3));
            CHECK(!empty(filled));
            CHECK(vector_capacity(filled).native_value() >= 3);
            CHECK(count(defaults) == uint64(2));
        }
        CHECK(vector_filled(int64(0), inner).empty());
        CHECK(allocations == before);
    }

	nested_vector values;
	auto original = row("first");
	values.append(original);
	values.append(row("second"));
	original.set(uint64(0), string("changed"));
	CHECK(values.at(uint64(0)).at(uint64(0)).equals("first"));
	auto extracted = values.at(uint64(0));
	extracted.clear();
	CHECK(values.at(uint64(0)).size() == uint64(1));
	auto copied = values;
	copied.set(uint64(0), row("copy"));
	CHECK(values.at(uint64(0)).at(uint64(0)).equals("first"));

	auto cursor = values.iterate();
	auto first = cursor.value();
	values.append(row("not visited"));
	values.set(uint64(1), row("not observed after preservation"));
	cursor.next();
	CHECK(cursor.value().at(uint64(0)).equals("second"));
	cursor.next();
	CHECK(!cursor.valid());
	values.clear();
	CHECK(first.at(uint64(0)).equals("first"));

	auto outer = surviving_outer();
	auto inner = outer.value();
	auto inner_cursor = inner.iterate();
	inner.clear();
	CHECK(inner_cursor.value().equals("long_first_value_that_requires_owned_storage"));
	inner_cursor.next();
	CHECK(!inner_cursor.valid());
	outer.next();
	CHECK(outer.value().at(uint64(0)).equals("second"));
	outer.next();
	CHECK(!outer.valid());
	auto escaped = surviving_inner();
	CHECK(escaped.value().equals("long_first_value_that_requires_owned_storage"));

	auto defaults = surviving_defaults();
	CHECK(defaults.value().at(uint64(0)).equals("default-owned"));
	defaults.next();
	CHECK(!defaults.valid());
	bool rejected = false;
	try { (void)copied.at(uint64(99)); }
	catch (...) { rejected = true; }
	CHECK(rejected);
	CHECK(copied.at(uint64(0)).at(uint64(0)).equals("copy"));
	std::puts("PASS: nested/defaulted filled factories, copy independence, allocation-free observations and escaped cursors");
}
