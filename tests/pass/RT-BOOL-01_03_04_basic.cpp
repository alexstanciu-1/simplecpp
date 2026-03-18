#include <scpp/runtime.hpp>

// Primary requirements:
// - RT-BOOL-01 bool_t must wrap one boolean value and behave as a value type.
// - RT-BOOL-03 bool_t must support equality and inequality with bool_t.
// - RT-BOOL-04 bool_t must support logical operators required by CASTING.md.

int main() {
	using namespace scpp;

	const bool_t t(true);
	const bool_t f(false);

	if (!(t == bool_t(true))) {
		return 1;
	}
	if (!(t != f)) {
		return 2;
	}
	if (!((t && t) == bool_t(true))) {
		return 3;
	}
	if (!((t || f) == bool_t(true))) {
		return 4;
	}
	if (!((!f) == bool_t(true))) {
		return 5;
	}

	return 0;
}
