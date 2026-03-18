#include <scpp/runtime.hpp>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Primary requirements:
// - RT-SH-03 shared_p<T> == shared_p<T> and != must compare identity.
// - RT-SH-04 shared_p<T> == null and != null must test empty/null state.

int main() {
	using namespace scpp;

	auto a = shared<Demo>(1);
	auto b = a;
	auto c = shared<Demo>(1);
	shared_p<Demo> empty = null;

	if (!(a == b)) {
		return 1;
	}
	if (!(a != c)) {
		return 2;
	}
	if (!(empty == null)) {
		return 3;
	}
	if (!(a != null)) {
		return 4;
	}

	return 0;
}
