#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-UQ-05.
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
// RT-UQ-05 Relational operators for unique_p<T> must remain unavailable.

int main() {
	using namespace scpp;
	auto a = unique<Demo>(1);
	auto b = unique<Demo>(2);
	return a < b;
}
