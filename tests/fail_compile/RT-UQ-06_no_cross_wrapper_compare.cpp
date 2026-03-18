#include <scpp/runtime.hpp>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Expected compile failure:
// RT-UQ-06 Cross-wrapper comparison with shared_p<T> must remain unavailable.

int main() {
	using namespace scpp;
	auto a = unique<Demo>(1);
	auto b = shared<Demo>(1);
	return a == b;
}
