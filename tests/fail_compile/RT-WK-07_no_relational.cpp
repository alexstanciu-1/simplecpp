#include <scpp/runtime.hpp>

// Test-only host helper type.
// This struct exists only to exercise runtime ownership/reference mechanics.
// It is NOT a generated Simple C++ type.
// Native C++ fields are intentionally allowed here because this test is not
// validating generated-language field typing; it is validating runtime object identity/lifetime behavior.
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
