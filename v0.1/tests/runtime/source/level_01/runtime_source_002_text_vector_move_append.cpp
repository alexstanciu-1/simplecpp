#include "tests/runtime/runtime_test_common.hpp"

int main() {
	scpp::vector_t<scpp::string_t> source;
	source.append(scpp::string_t("alpha"));
	source.append(scpp::string_t("beta"));
	source.append(scpp::string_t("gamma"));

	scpp::vector_t<scpp::string_t> target;
	target.reserve(3);

	scpp::source::source_text_vector_move_append(target, source, scpp::int_t<>(1));

	assert(target.size() == 1U);
	assert(target.at(0).native_value() == "beta");
	assert(source.at(0).native_value() == "alpha");
	assert(source.at(1).native_value().empty());
	assert(source.at(2).native_value() == "gamma");

	runtime_test::expect_throw<scpp::runtime_error>([&source, &target]() {
		scpp::source::source_text_vector_move_append(target, source, scpp::int_t<>(-1));
	});
	runtime_test::expect_throw<scpp::runtime_error>([&source, &target]() {
		scpp::source::source_text_vector_move_append(target, source, scpp::int_t<>(3));
	});

	return 0;
}
