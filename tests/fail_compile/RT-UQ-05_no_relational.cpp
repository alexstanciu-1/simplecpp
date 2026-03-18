#include <scpp/runtime.hpp>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Expected compile failure:
// RT-UQ-05 Relational operators for unique_p<T> must remain unavailable.

int main() {
	using namespace scpp;
	auto a = unique<Demo>(1);
	auto b = unique<Demo>(2);
	return a < b;
}
