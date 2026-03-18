#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-CGEN-01, RT-CGEN-02, RT-CGEN-03, RT-CGEN-04, and RT-CGEN-05.
// Primary requirements:
// - RT-CGEN-01 generated code must be able to construct runtime values explicitly through wrapper constructors or explicit wrapper entry points.
// - RT-CGEN-02 generated code must be able to create managed objects through create<T>(), shared<T>(), and unique<T>().
// - RT-CGEN-03 generated code must be able to derive non-owning references through weak(x).
// - RT-CGEN-04 generated code must not need to emit raw C++ allocation primitives for language-level object creation.
// - RT-CGEN-05 generated code must not need to name internal storage types such as std::string or smart-pointer types.

struct Demo {
	scpp::int_t value;
	explicit Demo(scpp::int_t v) : value(v) {
	}
};

int main() {
	using namespace scpp;

	auto a = int_t(12);
	auto b = float_t(4.5);
	auto s = string_t("abc");
	auto n = null;
	nullable<int_t> maybe = n;
	auto p1 = create<Demo>(int_t(1));
	auto p2 = shared<Demo>(int_t(2));
	auto p3 = unique<Demo>(int_t(3));
	auto w = weak(p1);

	if (a.native_value() != 12) return 1;
	if (b.native_value() != 4.5) return 2;
	if (!(s == string_t("abc"))) return 3;
	if (!(maybe == null)) return 4;
	if (p1 == null || p2 == null || p3 == null || w == null) return 5;

	return 0;
}
