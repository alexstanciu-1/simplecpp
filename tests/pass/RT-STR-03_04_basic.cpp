#include <scpp/runtime.hpp>

// Primary requirements:
// - RT-STR-03 string_t must support + and += with string_t only.
// - RT-STR-04 string_t must support lexicographic comparison against string_t.

int main() {
	using namespace scpp;

	string_t a("ab");
	string_t b("cd");
	string_t c = a + b;

	if (!(c == string_t("abcd"))) {
		return 1;
	}

	a += string_t("z");
	if (!(a == string_t("abz"))) {
		return 2;
	}

	if (!(string_t("abc") < string_t("abd"))) {
		return 3;
	}
	if (!(string_t("abd") > string_t("abc"))) {
		return 4;
	}
	if (!(string_t("abc") <= string_t("abc"))) {
		return 5;
	}
	if (!(string_t("abc") >= string_t("abc"))) {
		return 6;
	}

	return 0;
}
