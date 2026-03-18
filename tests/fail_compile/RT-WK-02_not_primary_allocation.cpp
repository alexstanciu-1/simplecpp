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
// RT-WK-02 weak_p<T> must not be a primary allocation result.
// There must be no weak<T>(...) allocation helper.

int main() {
	using namespace scpp;
	auto x = weak<Demo>(1);
	(void)x;
	return 0;
}
