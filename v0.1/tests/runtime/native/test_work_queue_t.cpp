#include "test_common.hpp"

#include "scpp/work_queue_t.hpp"
#include "scpp/string_t.hpp"

#include <cassert>
#include <utility>

namespace {

void test_fifo_and_growth() {
	scpp::work_queue_t<int> queue;
	assert(queue.empty().native_value());
	assert(queue.count() == 0U);
	assert(queue.capacity() == 0U);

	for (int i = 0; i < 10; ++i) {
		queue.push_back(i);
	}

	assert(!queue.empty().native_value());
	assert(queue.count() == 10U);
	assert(queue.capacity() >= 10U);

	for (int i = 0; i < 10; ++i) {
		assert(queue.pop_front() == i);
	}
	assert(queue.empty().native_value());
	assert(queue.count() == 0U);
}

void test_wraparound_order() {
	scpp::work_queue_t<int> queue(4);
	assert(queue.capacity() == 4U);

	queue.push_back(1);
	queue.push_back(2);
	queue.push_back(3);
	queue.push_back(4);
	assert(queue.pop_front() == 1);
	assert(queue.pop_front() == 2);

	queue.push_back(5);
	queue.push_back(6);
	assert(queue.count() == 4U);

	assert(queue.pop_front() == 3);
	assert(queue.pop_front() == 4);
	assert(queue.pop_front() == 5);
	assert(queue.pop_front() == 6);
	assert(queue.empty().native_value());
}

void test_wraparound_then_growth_preserves_order() {
	scpp::work_queue_t<int> queue(4);
	queue.push_back(10);
	queue.push_back(11);
	queue.push_back(12);
	queue.push_back(13);
	assert(queue.pop_front() == 10);
	assert(queue.pop_front() == 11);

	queue.push_back(14);
	queue.push_back(15);
	queue.push_back(16);
	assert(queue.capacity() >= 5U);

	for (const int expected : {12, 13, 14, 15, 16}) {
		assert(queue.pop_front() == expected);
	}
	assert(queue.empty().native_value());
}

void test_clear_retains_capacity() {
	scpp::work_queue_t<scpp::string_t> queue;
	queue.reserve(8);
	queue.push_back(scpp::string_t("a"));
	queue.push_back(scpp::string_t("b"));
	const auto capacity = queue.capacity();

	queue.clear();
	assert(queue.empty().native_value());
	assert(queue.count() == 0U);
	assert(queue.capacity() == capacity);

	queue.push_back(scpp::string_t("again"));
	assert(queue.pop_front().native_value() == "again");
}

void test_empty_pop_rejected() {
	scpp::work_queue_t<int> queue;
	scpp_test::expect_throw<scpp::runtime_error>([&]() {
		static_cast<void>(queue.pop_front());
	});
}

} // namespace

int main() {
	test_fifo_and_growth();
	test_wraparound_order();
	test_wraparound_then_growth_preserves_order();
	test_clear_retains_capacity();
	test_empty_pop_rejected();
	return 0;
}
