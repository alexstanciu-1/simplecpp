#include <scpp/runtime.hpp>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Expected compile failure:
// RT-WK-07 Relational operators for weak_p<T> must remain unavailable.

int main() {
	using namespace scpp;
	auto owner = shared<Demo>(1);
	auto a = weak(owner);
	auto b = weak(owner);
	return a < b;
}
