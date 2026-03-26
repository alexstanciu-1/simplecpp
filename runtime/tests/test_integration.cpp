#include "test_common.hpp"

namespace scpp_test::interfaces {

struct reader {
	virtual ~reader() = default;
	virtual scpp::string_t read() const = 0;
	virtual void bump(scpp::int_t step) = 0;
};

struct counter_reader final : reader {
	scpp::string_t label;
	scpp::int_t value;

	counter_reader(scpp::string_t initial_label, scpp::int_t initial_value)
		: label(std::move(initial_label)), value(std::move(initial_value)) {
	}

	scpp::string_t read() const override {
		return label + scpp::string_t(":") + scpp::cast<scpp::string_t>(value);
	}

	void bump(scpp::int_t step) override {
		value = value + step;
	}
};

} // namespace scpp_test::interfaces

namespace scpp_test::constants::config {

const scpp::int_t default_left(4);
const scpp::int_t default_right(6);
const scpp::int_t pipeline_factor(3);
const scpp::int_t pipeline_fallback(5);
const scpp::string_t pipeline_label("pipe");

} // namespace scpp_test::constants::config

namespace scpp_test::constants::ops {

scpp::int_t combine(
	scpp::int_t left = ::scpp_test::constants::config::default_left,
	scpp::int_t right = ::scpp_test::constants::config::default_right);

scpp::int_t bias(
	scpp::int_t value,
	scpp::int_t delta = ::scpp_test::constants::config::default_right);

scpp::string_t describe(
	scpp::int_t value = ::scpp_test::constants::config::default_left);

scpp::int_t combine(scpp::int_t left, scpp::int_t right) {
	return left + right;
}

scpp::int_t bias(scpp::int_t value, scpp::int_t delta) {
	return value + delta;
}

scpp::string_t describe(scpp::int_t value) {
	return scpp::string_t("v=") + scpp::cast<scpp::string_t>(value);
}

} // namespace scpp_test::constants::ops

namespace scpp_test::pipeline {

struct formatter {
	virtual ~formatter() = default;
	virtual scpp::string_t format(scpp::int_t value) const = 0;
};

struct label_formatter final : formatter {
	scpp::string_t format(scpp::int_t value) const override {
		return ::scpp_test::constants::config::pipeline_label + scpp::string_t(":")
			+ scpp::cast<scpp::string_t>(value);
	}
};

scpp::string_t run(
	scpp::shared_p<formatter> service,
	scpp::nullable<scpp::int_t> seed = scpp::null,
	scpp::int_t factor = ::scpp_test::constants::config::pipeline_factor);

scpp::string_t run(scpp::shared_p<formatter> service, scpp::nullable<scpp::int_t> seed, scpp::int_t factor) {
	const auto base = seed.value_or(::scpp_test::constants::config::pipeline_fallback);
	return service->format(base * factor);
}

} // namespace scpp_test::pipeline

// Verifies interface dispatch stays virtual after shared_p upcast and preserves shared ownership identity.
static void test_interface_dispatch_shared_aliasing() {
	auto concrete = scpp::shared<scpp_test::interfaces::counter_reader>(scpp::string_t("n"), scpp::int_t(7));
	scpp::shared_p<scpp_test::interfaces::reader> as_interface = concrete;

	assert(as_interface->read().native_value() == "n:7");
	assert(as_interface.get() == concrete.get());

	concrete->bump(scpp::int_t(2));
	assert(as_interface->read().native_value() == "n:9");
}

// Verifies weak lock returns a shared owner that can still be upcast for interface dispatch without losing alias semantics.
static void test_interface_dispatch_after_weak_lock() {
	auto concrete = scpp::shared<scpp_test::interfaces::counter_reader>(scpp::string_t("w"), scpp::int_t(3));
	auto observer = scpp::weak(concrete);
	const auto locked = observer.lock();
	scpp::shared_p<scpp_test::interfaces::reader> as_interface = locked;

	assert(observer.expired().native_value() == false);
	assert(locked.has_value().native_value() == true);
	assert(as_interface->read().native_value() == "w:3");

	as_interface->bump(scpp::int_t(4));
	assert(concrete->read().native_value() == "w:7");
}

// Verifies declaration-site defaults can name constants from another namespace and stay consistent across calls.
static void test_default_args_with_cross_namespace_constants() {
	assert(scpp_test::constants::ops::combine().native_value() == 10);
	assert(scpp_test::constants::ops::combine(scpp::int_t(10)).native_value() == 16);
	assert(scpp_test::constants::ops::bias(scpp::int_t(1)).native_value() == 7);
	assert(scpp_test::constants::ops::describe().native_value() == "v=4");
}

// Verifies a mixed pipeline can combine nullable fallback, interface dispatch, and cross-namespace constants in one flow.
static void test_nullable_interface_constants_pipeline() {
	auto formatter = scpp::shared<scpp_test::pipeline::label_formatter>();
	scpp::shared_p<scpp_test::pipeline::formatter> service = formatter;

	assert(scpp_test::pipeline::run(service).native_value() == "pipe:15");
	assert(scpp_test::pipeline::run(service, scpp::nullable<scpp::int_t>(scpp::int_t(4))).native_value() == "pipe:12");
	assert(scpp_test::pipeline::run(service, scpp::nullable<scpp::int_t>(scpp::int_t(4)), scpp::int_t(2)).native_value() == "pipe:8");
}

int main() {
	test_interface_dispatch_shared_aliasing();
	test_interface_dispatch_after_weak_lock();
	test_default_args_with_cross_namespace_constants();
	test_nullable_interface_constants_pipeline();
	return 0;
}
