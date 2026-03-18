#include <scpp/runtime.hpp>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Primary requirements:
// - RT-WK-03 weak_p<T> must be derivable from an owning managed value through weak(x).
// - RT-WK-04 expired weak references must behave as null in comparison contexts.
// - RT-WK-05 weak_p<T> == weak_p<T> and != must follow resolved-identity / expired-null rules.
// - RT-WK-06 weak_p<T> == null and != null must test empty/expired state.

int main() {
	using namespace scpp;

	weak_p<Demo> expired;
	{
		auto owner = shared<Demo>(1);
		auto w1 = weak(owner);
		auto w2 = weak(owner);

		if (!(w1 == w2)) {
			return 1;
		}
		if (!(w1 != null)) {
			return 2;
		}

		expired = w1;
	}

	if (!(expired == null)) {
		return 3;
	}

	return 0;
}
