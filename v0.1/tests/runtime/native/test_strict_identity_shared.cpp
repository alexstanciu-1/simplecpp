#include "test_common.hpp"
#include "operators/identity/strict_identity.hpp"

namespace {

struct base { int value = 7; };
struct derived final : base {};
struct unrelated final { int value = 7; };
struct prefix { int padding = 3; };
struct multiple final : prefix, base {};
struct virtual_base : virtual base {};
struct virtual_derived final : virtual_base {};

template <typename Left, typename Right>
void expect_identity(const Left &left, const Right &right, bool expected) {
	assert(scpp::identical(left, right).native_value() == expected);
	assert(scpp::identical(right, left).native_value() == expected);
	assert(scpp::not_identical(left, right).native_value() == !expected);
	assert(scpp::not_identical(right, left).native_value() == !expected);
}

void test_shared_views() {
	auto concrete = scpp::create<derived>();
	scpp::shared_p<base> parent = concrete;
	auto other = scpp::create<derived>();
	expect_identity(concrete, concrete, true);
	expect_identity(concrete, parent, true);
	expect_identity(other, parent, false);
	expect_identity(other, concrete, false);
	expect_identity(concrete, scpp::create<unrelated>(), false);

	// The base subobject has a different address; comparing void* would be wrong.
	auto composite = scpp::create<multiple>();
	scpp::shared_p<base> adjusted = composite;
	assert(static_cast<const void *>(composite.get()) != static_cast<const void *>(adjusted.get()));
	expect_identity(composite, adjusted, true);
	auto virtual_child = scpp::create<virtual_derived>();
	scpp::shared_p<base> virtual_parent = virtual_child;
	expect_identity(virtual_child, virtual_parent, true);
}

void test_nullable_views() {
	auto concrete = scpp::create<derived>();
	scpp::shared_p<base> parent = concrete;
	scpp::nullable<scpp::shared_p<derived>> optional_child = concrete;
	scpp::nullable<scpp::shared_p<base>> optional_parent = parent;
	expect_identity(optional_child, parent, true);
	expect_identity(concrete, optional_parent, true);
	expect_identity(optional_child, optional_parent, true);
	expect_identity(optional_child, scpp::create<base>(), false);

	scpp::shared_p<derived> empty_child;
	scpp::shared_p<base> empty_parent;
	scpp::nullable<scpp::shared_p<derived>> absent_child;
	scpp::nullable<scpp::shared_p<base>> absent_parent;
	expect_identity(empty_child, empty_parent, true);
	expect_identity(absent_child, absent_parent, true);
	expect_identity(absent_child, empty_parent, true);
	expect_identity(optional_child, empty_parent, false);
	expect_identity(absent_child, parent, false);
	expect_identity(optional_child, scpp::null_t{}, false);
	expect_identity(absent_child, scpp::null_t{}, true);
}

} // namespace

int main() {
	test_shared_views();
	test_nullable_views();
	// Scalar strict identity must not acquire ordinary numeric conversion semantics.
	expect_identity(scpp::int_t<>(1), scpp::bool_t(true), false);
}
