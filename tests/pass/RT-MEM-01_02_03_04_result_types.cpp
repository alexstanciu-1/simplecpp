#include <scpp/runtime.hpp>
#include <type_traits>

struct Demo {
	int value;
	explicit Demo(int v) : value(v) {
	}
};

// Primary requirements:
// - RT-MEM-01 create<T>(...) must exist as the default managed creation helper.
// - RT-MEM-02 create<T>(...) must currently be equivalent to shared<T>(...) in ownership result.
// - RT-MEM-03 shared<T>(...) must produce shared_p<T>.
// - RT-MEM-04 unique<T>(...) must produce unique_p<T>.

int main() {
	using namespace scpp;

	static_assert(std::is_same_v<decltype(create<Demo>(1)), shared_p<Demo>>);
	static_assert(std::is_same_v<decltype(shared<Demo>(1)), shared_p<Demo>>);
	static_assert(std::is_same_v<decltype(unique<Demo>(1)), unique_p<Demo>>);

	auto a = create<Demo>(1);
	auto b = shared<Demo>(2);
	auto c = unique<Demo>(3);

	if (a == null) {
		return 1;
	}
	if (b == null) {
		return 2;
	}
	if (c == null) {
		return 3;
	}

	return 0;
}
