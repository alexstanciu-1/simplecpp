#include <scpp/runtime.hpp>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Expected compile failure:
// RT-SH-06 Cross-wrapper comparison with weak_p<T> must remain unavailable.

int main() {
	using namespace scpp;
	auto owner = shared<Demo>(1);
	auto ref = weak(owner);
	return owner == ref;
}
