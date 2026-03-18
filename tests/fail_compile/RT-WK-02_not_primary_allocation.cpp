#include <scpp/runtime.hpp>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Expected compile failure:
// RT-WK-02 weak_p<T> must not be a primary allocation result.
// There must be no weak<T>(...) allocation helper.

int main() {
	using namespace scpp;
	auto x = weak<Demo>(1);
	(void)x;
	return 0;
}
