#include "test_common.hpp"

#include "scpp/row_arena_t.hpp"
#include "scpp/string_t.hpp"

#include <cassert>
#include <cstdint>
#include <stdexcept>

namespace {

struct row_record final {
	scpp::string_t key;
	std::int64_t value = 0;
};

void test_default_uint32_ids() {
	scpp::row_arena_t<row_record> arena;
	arena.reserve(4);
	assert(arena.capacity() >= 4);

	const auto first = arena.append(row_record{scpp::string_t("first"), 10});
	const auto second = arena.append(row_record{scpp::string_t("second"), 20});

	assert(first == 1U);
	assert(second == 2U);
	assert(arena.size() == 2U);
	assert(arena.count_id() == 2U);
	assert(arena.can_read(first));
	assert(!arena.can_read(0U));
	assert(arena.get(first).key.native_value() == "first");

	arena.set(second, row_record{scpp::string_t("second_updated"), 30});
	assert(arena.get(second).key.native_value() == "second_updated");
	assert(arena.get(second).value == 30);

	arena.clear();
	assert(arena.size() == 0U);
	assert(!arena.can_read(first));

	const auto reused = arena.append(row_record{scpp::string_t("reused"), 40});
	assert(reused == 1U);
	assert(arena.get(reused).key.native_value() == "reused");
}

void test_custom_id_width() {
	scpp::row_arena_t<row_record, std::uint16_t> arena;
	const auto id = arena.append(row_record{scpp::string_t("small"), 1});
	static_assert(std::is_same_v<decltype(id), const std::uint16_t>);
	assert(id == 1U);
	assert(arena.get(id).value == 1);
}

void test_zero_id_rejected() {
	scpp::row_arena_t<row_record> arena;
	static_cast<void>(arena.append(row_record{scpp::string_t("first"), 10}));

	bool rejected = false;
	try {
		static_cast<void>(arena.get(0U));
	} catch (const std::exception &) {
		rejected = true;
	}
	assert(rejected);
}

} // namespace

int main() {
	test_default_uint32_ids();
	test_custom_id_width();
	test_zero_id_rejected();
	return 0;
}
