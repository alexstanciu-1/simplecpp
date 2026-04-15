#include "test_common.hpp"

#include <sstream>

namespace {

scpp::int_t take_int(scpp::int_t value) {
	return value;
}

scpp::float_t take_float(scpp::float_t value) {
	return value;
}

scpp::bool_t take_bool(scpp::bool_t value) {
	return value;
}

scpp::string_t take_string(scpp::string_t value) {
	return value;
}

scpp::shared_p<scpp::hash_t<scpp::mixed_t>> take_shared_table(
	scpp::shared_p<scpp::hash_t<scpp::mixed_t>> value
) {
	return value;
}

static void test_value_t_cast_bridge_and_exact_accessors() {
	scpp::mixed_t int_value(scpp::int_t(7));
	scpp::mixed_t float_value(scpp::float_t(2.5));
	scpp::mixed_t bool_value(scpp::bool_t(true));
	scpp::mixed_t string_value(scpp::string_t("Alex"));
	scpp::mixed_t null_value(scpp::null_t{});

	assert(scpp::cast<scpp::int_t>(int_value).native_value() == 7);
	assert(scpp::cast<scpp::float_t>(int_value).native_value() == 7.0);
	assert(scpp::cast<scpp::int_t>(float_value).native_value() == 2);
	assert(scpp::cast<scpp::bool_t>(int_value).native_value() == true);
	assert(scpp::cast<scpp::bool_t>(float_value).native_value() == true);
	assert(scpp::cast<scpp::string_t>(bool_value).native_value() == "1");
	assert(scpp::cast<scpp::string_t>(null_value).native_value().empty());
	assert(scpp::cast<scpp::string_t>(string_value).native_value() == "Alex");
	assert(scpp::cast<scpp::bool_t>(scpp::mixed_t(scpp::string_t("true"))).native_value() == true);
	assert(scpp::cast<scpp::int_t>(scpp::mixed_t(scpp::string_t("15"))).native_value() == 15);
	assert(scpp::cast<scpp::float_t>(scpp::mixed_t(scpp::string_t("2.75"))).native_value() == 2.75);

	assert(take_int(scpp::cast<scpp::int_t>(int_value)).native_value() == 7);
	assert(take_float(scpp::cast<scpp::float_t>(int_value)).native_value() == 7.0);
	assert(take_float(scpp::cast<scpp::float_t>(float_value)).native_value() == 2.5);
	assert(take_bool(scpp::cast<scpp::bool_t>(bool_value)).native_value() == true);
	assert(take_string(scpp::cast<scpp::string_t>(string_value)).native_value() == "Alex");

	assert(int_value.type() == scpp::mixed_t::mixed_type::int_v);
	assert(bool_value.is_bool().native_value() == true);
	assert(string_value.is_string().native_value() == true);
	assert(int_value.try_get_int() != nullptr);
	assert(int_value.try_get_string() == nullptr);
	assert(int_value.get_int().native_value() == 7);
	assert(string_value.get_string().native_value() == "Alex");

	scpp_test::expect_throw<std::runtime_error>([&]() {
		(void)scpp::cast<scpp::int_t>(null_value);
	});
	scpp_test::expect_throw<std::runtime_error>([&]() {
		(void)take_int(scpp::cast<scpp::int_t>(scpp::mixed_t(scpp::string_t("oops"))));
	});
	scpp_test::expect_throw<std::runtime_error>([&]() {
		(void)take_bool(scpp::cast<scpp::bool_t>(scpp::mixed_t(scpp::string_t("yes"))));
	});
}

static void test_value_t_operator_dispatch_numeric_and_string() {
	scpp::mixed_t i1(scpp::int_t(10));
	scpp::mixed_t i2(scpp::int_t(3));
	scpp::mixed_t f1(scpp::float_t(2.5));
	scpp::mixed_t s1(scpp::string_t("Al"));
	scpp::mixed_t s2(scpp::string_t("ex"));

	auto sum_ii = i1 + i2;
	assert(sum_ii.kind() == scpp::mixed_t::kind_t::int_v);
	assert(sum_ii.int_value().native_value() == 13);

	auto sum_if = i1 + f1;
	assert(sum_if.kind() == scpp::mixed_t::kind_t::float_v);
	assert(sum_if.float_value().native_value() == 12.5);

	auto sum_fi = f1 + i2;
	assert(sum_fi.kind() == scpp::mixed_t::kind_t::float_v);
	assert(sum_fi.float_value().native_value() == 5.5);

	auto diff = i1 - i2;
	assert(diff.int_value().native_value() == 7);
	assert((i1 * i2).int_value().native_value() == 30);
	assert((i1 / i2).int_value().native_value() == 3);
	assert((i1 % i2).int_value().native_value() == 1);
	assert((~i2).int_value().native_value() == ~3LL);
	assert((i1 & i2).int_value().native_value() == (10LL & 3LL));
	assert((i1 | i2).int_value().native_value() == (10LL | 3LL));
	assert((i1 ^ i2).int_value().native_value() == (10LL ^ 3LL));
	assert((i1 << i2).int_value().native_value() == (10LL << 3LL));
	assert((i1 >> i2).int_value().native_value() == (10LL >> 3LL));
	assert((s1 + s2).string_if()->native_value() == "Alex");

	assert((+i2).int_value().native_value() == 3);
	assert((-i2).int_value().native_value() == -3);
	assert((+f1).float_value().native_value() == 2.5);
	assert((-f1).float_value().native_value() == -2.5);

	scpp_test::expect_throw<std::runtime_error>([&]() {
		(void)(s1 - s2);
	});
}

static void test_value_t_comparisons_and_logical() {
	scpp::mixed_t i1(scpp::int_t(10));
	scpp::mixed_t i2(scpp::int_t(3));
	scpp::mixed_t f1(scpp::float_t(10.0));
	scpp::mixed_t b1(scpp::bool_t(true));
	scpp::mixed_t b2(scpp::bool_t(false));
	scpp::mixed_t n1(scpp::null_t{});
	scpp::mixed_t s1(scpp::string_t(""));

	assert((i1 == f1).native_value() == true);
	assert((i1 != i2).native_value() == true);
	assert((i2 < i1).native_value() == true);
	assert((i2 <= i1).native_value() == true);
	assert((i1 > i2).native_value() == true);
	assert((i1 >= i2).native_value() == true);
	assert((b1 && b2).native_value() == false);
	assert((b1 || b2).native_value() == true);
	assert((!b2).native_value() == true);
	assert((i1 && i2).native_value() == true);
	assert((scpp::mixed_t(scpp::int_t(0)) || scpp::mixed_t(scpp::float_t(1.0))).native_value() == true);
	assert((!scpp::mixed_t(scpp::int_t(0))).native_value() == true);
	assert((n1 == scpp::mixed_t(scpp::null_t{})).native_value() == true);
	assert((n1 == s1).native_value() == true);

	scpp_test::expect_throw<std::runtime_error>([&]() {
		(void)(b1 == i1);
	});
	scpp_test::expect_throw<std::runtime_error>([&]() {
		(void)(scpp::mixed_t(scpp::string_t("x")) && b1);
	});
}

static void test_value_t_assignment_and_increment() {
	scpp::mixed_t i1(scpp::int_t(10));
	scpp::mixed_t i2(scpp::int_t(3));
	scpp::mixed_t f1(scpp::float_t(5.5));

	i1 += i2;
	assert(i1.int_value().native_value() == 13);
	i1 -= i2;
	assert(i1.int_value().native_value() == 10);
	i1 *= i2;
	assert(i1.int_value().native_value() == 30);
	i1 /= i2;
	assert(i1.int_value().native_value() == 10);
	i1 %= i2;
	assert(i1.int_value().native_value() == 1);
	i1 |= i2;
	assert(i1.int_value().native_value() == (1LL | 3LL));
	i1 &= i2;
	assert(i1.int_value().native_value() == 3);
	i1 ^= scpp::mixed_t(scpp::int_t(1));
	assert(i1.int_value().native_value() == 2);
	i1 <<= scpp::mixed_t(scpp::int_t(2));
	assert(i1.int_value().native_value() == 8);
	i1 >>= scpp::mixed_t(scpp::int_t(1));
	assert(i1.int_value().native_value() == 4);

	f1 += scpp::mixed_t(scpp::int_t(2));
	assert(f1.float_value().native_value() == 7.5);
	scpp::mixed_t widening(scpp::int_t(4));
	widening += scpp::mixed_t(scpp::float_t(1.5));
	assert(widening.kind() == scpp::mixed_t::kind_t::float_v);
	assert(widening.float_value().native_value() == 5.5);
	f1 -= scpp::mixed_t(scpp::float_t(0.5));
	assert(f1.float_value().native_value() == 7.0);
	f1 *= scpp::mixed_t(scpp::int_t(2));
	assert(f1.float_value().native_value() == 14.0);
	f1 /= scpp::mixed_t(scpp::float_t(2.0));
	assert(f1.float_value().native_value() == 7.0);

	scpp::mixed_t inc_int(scpp::int_t(1));
	scpp::mixed_t inc_float(scpp::float_t(1.5));
	assert((++inc_int).int_value().native_value() == 2);
	assert((inc_int++).int_value().native_value() == 2);
	assert(inc_int.int_value().native_value() == 3);
	assert((--inc_float).float_value().native_value() == 0.5);
	assert((inc_float--).float_value().native_value() == 0.5);
	assert(inc_float.float_value().native_value() == -0.5);

	scpp::mixed_t concat(scpp::string_t("x"));
	concat += scpp::mixed_t(scpp::string_t("y"));
	assert(concat.string_if()->native_value() == "xy");
}

static void test_value_t_array_copy_on_write_param_and_nested_copy() {
	scpp::mixed_t root(scpp::shared<scpp::hash_t<scpp::mixed_t>>());
	root[scpp::string_t("name")] = scpp::mixed_t(scpp::string_t("outside"));
	root[scpp::string_t("child")] = scpp::mixed_t(scpp::shared<scpp::hash_t<scpp::mixed_t>>());
	root[scpp::string_t("child")][scpp::string_t("count")] = scpp::mixed_t(scpp::int_t(1));

	auto fill = [](scpp::mixed_t arr) {
		arr[scpp::string_t("name")] = scpp::mixed_t(scpp::string_t("inside"));
		assert(arr.get(scpp::string_t("name")).string_if()->native_value() == "inside");
	};
	fill(root);
	assert(root.get(scpp::string_t("name")).string_if()->native_value() == "outside");

	scpp::mixed_t child_copy = root.get(scpp::string_t("child"));
	child_copy[scpp::string_t("count")] = scpp::mixed_t(scpp::int_t(99));
	assert(root.get(scpp::string_t("child")).get(scpp::string_t("count")).int_value().native_value() == 1);
	assert(child_copy.get(scpp::string_t("count")).int_value().native_value() == 99);
}


static void test_value_t_boxed_table_helpers() {
	scpp::mixed_t boxed = scpp::mixed_t{scpp::shared<scpp::hash_t<scpp::mixed_t>>()};
	boxed.append(scpp::mixed_t(scpp::int_t(10)));
	boxed.append(scpp::mixed_t(scpp::int_t(20)));

	assert(boxed.size().native_value() == 2);
	assert(boxed.empty() == false);
	assert(boxed.at(scpp::int_t(0)).int_value().native_value() == 10);
	assert(boxed.at(scpp::int_t(1)).int_value().native_value() == 20);

	boxed[scpp::string_t("name")] = scpp::mixed_t(scpp::string_t("Alex"));
	assert(boxed.at(scpp::string_t("name")).string_if()->native_value() == "Alex");

	scpp::mixed_t empty_boxed = scpp::mixed_t{scpp::shared<scpp::hash_t<scpp::mixed_t>>()};
	assert(empty_boxed.size().native_value() == 0);
	assert(empty_boxed.empty() == true);

	scpp_test::expect_throw<std::runtime_error>([&]() {
		(void) scpp::mixed_t(scpp::int_t(1)).size();
	});
	scpp_test::expect_throw<std::runtime_error>([&]() {
		(void) scpp::mixed_t(scpp::string_t("x")).empty();
	});
	scpp_test::expect_throw<std::out_of_range>([&]() {
		(void) boxed.at(scpp::int_t(99));
	});
}

static void test_value_t_table_access_and_identity() {
	auto shared = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	shared->set(scpp::string_t("name"), scpp::mixed_t(scpp::string_t("Alex")));
	shared->set(scpp::string_t("child"), scpp::mixed_t(scpp::null_t{}));

	scpp::mixed_t shared_value(shared);
	scpp::mixed_t same_shared(shared);
	scpp::mixed_t weak_value{scpp::weak_p<scpp::hash_t<scpp::mixed_t>>(shared)};

	assert((shared_value == same_shared).native_value() == true);
	assert((shared_value == weak_value).native_value() == true);
	assert((weak_value == same_shared).native_value() == true);
	assert((weak_value == scpp::mixed_t(scpp::weak_p<scpp::hash_t<scpp::mixed_t>>(shared))).native_value() == true);
	assert(shared_value.get(scpp::string_t("name")).string_if()->native_value() == "Alex");
	assert(shared_value.get(scpp::string_t("missing")).is_null().native_value() == true);
	assert(take_shared_table(scpp::cast<scpp::shared_p<scpp::hash_t<scpp::mixed_t>>>(shared_value)).get() == shared.get());

	scpp::mixed_t null_value(scpp::null_t{});
	assert(null_value.get(scpp::string_t("anything")).is_null().native_value() == true);

	shared_value = scpp::null_t{};
	same_shared = scpp::null_t{};
	shared.reset();
	auto other_shared = scpp::shared<scpp::hash_t<scpp::mixed_t>>();
	scpp::mixed_t other_shared_value(other_shared);
	assert((other_shared_value == weak_value).native_value() == false);
	assert(weak_value.get(scpp::string_t("name")).is_null().native_value() == true);

	scpp_test::expect_throw<std::runtime_error>([&]() {
		auto owned_left = scpp::mixed_t(scpp::unique<scpp::hash_t<scpp::mixed_t>>());
		auto owned_right = scpp::mixed_t(scpp::unique<scpp::hash_t<scpp::mixed_t>>());
		(void)(owned_left == owned_right);
	});
}

} // namespace


static void test_dynamic_t_identity_and_explicit_conversion() {
	scpp::dynamic_t payload = scpp::dynamic_(scpp::table_kv_(scpp::string_t("id"), scpp::int_t(1))).value;
	scpp::mixed_t left(scpp::dynamic_box(payload));
	scpp::mixed_t right(scpp::dynamic_box(payload));

	assert(left.kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(right.kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert((left == right).native_value() == true);

	auto copied_hash = scpp::to_hash(payload);
	copied_hash[scpp::string_t("id")] = scpp::mixed_t(scpp::int_t(99));
	assert(left[scpp::string_t("id")].int_value().native_value() == 1);

	auto rebuilt_dynamic = scpp::to_dynamic(copied_hash);
	scpp::mixed_t rebuilt(scpp::dynamic_box(rebuilt_dynamic));
	assert(rebuilt.kind() == scpp::mixed_t::kind_t::dynamic_v);
	assert(rebuilt[scpp::string_t("id")].int_value().native_value() == 99);
	assert((left == rebuilt).native_value() == false);

	left[scpp::string_t("name")] = scpp::mixed_t(scpp::string_t("Alex"));
	assert(right[scpp::string_t("name")].string_if()->native_value() == "Alex");
}

static void test_dynamic_t_var_dump() {
	scpp::mixed_t value(scpp::dynamic_(
		scpp::table_kv_(scpp::string_t("id"), scpp::int_t(1)),
		scpp::table_kv_(scpp::string_t("name"), scpp::string_t("Alex"))
	));

	std::ostringstream buffer;
	auto *previous = std::cout.rdbuf(buffer.rdbuf());
	scpp::php::var_dump(value);
	std::cout.rdbuf(previous);

	const std::string dumped = buffer.str();
	assert(dumped.find("object(dynamic_t)(2)") != std::string::npos);
	assert(dumped.find('"' + std::string("id") + '"') != std::string::npos);
	assert(dumped.find('"' + std::string("name") + '"') != std::string::npos);
}

int main() {
	test_value_t_cast_bridge_and_exact_accessors();
	test_value_t_operator_dispatch_numeric_and_string();
	test_value_t_comparisons_and_logical();
	test_value_t_assignment_and_increment();
	test_value_t_array_copy_on_write_param_and_nested_copy();
	test_value_t_boxed_table_helpers();
	test_dynamic_t_identity_and_explicit_conversion();
	test_dynamic_t_var_dump();
	test_value_t_table_access_and_identity();
	return 0;
}
