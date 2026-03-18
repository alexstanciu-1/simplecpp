#include <scpp/runtime.hpp>
#include <stdexcept>

// Primary requirement:
// - RT-STR-08 Invalid string-to-primitive explicit conversion must route through one central throw/failure path.

int main() {
	using namespace scpp;

	try {
		(void)to_bool(string_t("yes"));
		return 1;
	} catch (const std::runtime_error &) {
	}

	try {
		(void)to_int(string_t("12x"));
		return 2;
	} catch (const std::runtime_error &) {
	}

	try {
		(void)to_float(string_t("abc"));
		return 3;
	} catch (const std::runtime_error &) {
	}

	return 0;
}
