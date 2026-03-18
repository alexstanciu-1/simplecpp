#include <scpp/runtime.hpp>
#include <utility>

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

// Primary requirements:
// - RT-UQ-03 unique_p<T> == unique_p<T> and != must compare identity.
// - RT-UQ-04 unique_p<T> == null and != null must test empty/null state.

int main() {
	using namespace scpp;

	auto a = unique<Demo>(1);
	auto b = unique<Demo>(1);
	auto same = std::move(a);

	if (!(same != b)) {
		return 1;
	}
	if (!(a == null)) {
		return 2;
	}
	if (!(same != null)) {
		return 3;
	}

	return 0;
}
