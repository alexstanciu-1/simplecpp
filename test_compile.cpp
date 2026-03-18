
#include "scpp/runtime.hpp"

// g++ -std=c++20 -Wall -Wextra -Werror -Iinclude src/*.cpp test_compile.cpp -o test

struct MyClass {
	int value;
	MyClass(int v = 0) : value(v) {}
};

int main() {
	scpp::int_t a(12);
	scpp::float_t b(4.5);
	auto c = a + b;
	scpp::string_t s("abc");
	scpp::nullable<scpp::int_t> x = scpp::null;
	auto o1 = scpp::create<MyClass>(1);
	auto o2 = scpp::shared<MyClass>(2);
	auto o3 = scpp::unique<MyClass>(3);
	auto ow = scpp::weak(o1);
	(void)c;
	(void)s;
	(void)x;
	(void)o1;
	(void)o2;
	(void)o3;
	(void)ow;
	return 0;
}
w