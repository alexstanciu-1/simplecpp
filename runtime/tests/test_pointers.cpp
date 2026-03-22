#include "test_common.hpp"

// Verifies shared ownership helpers and null-safe checked dereference.
static void test_shared_pointer_behavior() {
	const auto empty = scpp::shared_p<scpp_test::sample_object>(scpp::null);
	assert((empty == scpp::null).native_value() == true);
	assert(empty.has_value().native_value() == false);
	assert(empty.get() == nullptr);
	scpp_test::expect_throw<std::runtime_error>([&empty]() {
		(void)empty.deref();
	});

	const auto value = scpp::shared<scpp_test::sample_object>(scpp::int_t(21));
	assert(value.has_value().native_value() == true);
	assert((value != scpp::null).native_value() == true);
	assert(value.get() != nullptr);
	assert(value.deref().value.native_value() == 21);
	assert(value.arrow()->value.native_value() == 21);

	const auto same = value;
	assert((value == same).native_value() == true);
}

// Verifies unique ownership helpers, move-only behavior, and null-safe dereference.
static void test_unique_pointer_behavior() {
	auto empty = scpp::unique_p<scpp_test::sample_object>(scpp::null_ptr);
	assert((empty == scpp::null_ptr).native_value() == true);
	assert(empty.has_value().native_value() == false);
	scpp_test::expect_throw<std::runtime_error>([&empty]() {
		(void)empty.deref();
	});

	auto value = scpp::unique<scpp_test::sample_object>(scpp::int_t(8));
	assert(value.has_value().native_value() == true);
	assert(value.deref().value.native_value() == 8);
	assert(value.arrow()->value.native_value() == 8);

	auto moved = std::move(value);
	assert((value == scpp::null).native_value() == true);
	assert((moved != scpp::null).native_value() == true);
	assert(moved.deref().value.native_value() == 8);
}

// Verifies create(), weak(), lock(), and the current expired-based null semantics.
static void test_weak_pointer_behavior() {
	const auto created = scpp::create<scpp_test::sample_object>(scpp::int_t(33));
	assert(created.deref().value.native_value() == 33);

	auto owner = scpp::shared<scpp_test::sample_object>(scpp::int_t(55));
	const auto observer = scpp::weak(owner);
	assert(observer.expired().native_value() == false);
	assert((observer != scpp::null).native_value() == true);
	assert(observer.lock().deref().value.native_value() == 55);

	owner = scpp::shared_p<scpp_test::sample_object>(scpp::null);
	assert(observer.expired().native_value() == true);
	assert((observer == scpp::null).native_value() == true);
	assert((observer == scpp::null_ptr).native_value() == true);
	assert(observer.lock().has_value().native_value() == false);
}

int main() {
	test_shared_pointer_behavior();
	test_unique_pointer_behavior();
	test_weak_pointer_behavior();
	return 0;
}
