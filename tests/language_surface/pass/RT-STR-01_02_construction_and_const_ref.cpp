#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-STR-01 and RT-STR-02.
// Primary requirements:
// - RT-STR-01 string_t must wrap std::string internally while preserving a Simple C++ runtime-visible wrapper surface.
// - RT-STR-02 string_t must be passable by const & in generated/runtime-facing APIs by default.

static const scpp::string_t &echo_ref(const scpp::string_t &value) {
	return value;
}

int main() {
	using namespace scpp;

	const string_t a("abc");
	const string_t b(std::string("def"));

	if (!(echo_ref(a) == string_t("abc"))) {
		return 1;
	}
	if (!(echo_ref(b) == string_t("def"))) {
		return 2;
	}

	return 0;
}
