#include <scpp/runtime.hpp>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Expected compile failure:
// RT-SH-05 Relational operators for shared_p<T> must remain unavailable.

int main() {
	using namespace scpp;
	auto a = shared<Demo>(1);
	auto b = shared<Demo>(2);
	return a < b;
}
