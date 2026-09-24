#include "scpp/compiler.hpp"
#include "scpp/memory.hpp"
#include <iostream>
#include <iomanip>
using namespace scpp::compiler;
struct record { int value; explicit record(int v) : value(v) {} };
template<class E, class F> void rejection(F operation) {
	try { operation(); std::cout << "accepted\n"; }
	catch (const E &) { std::cout << "rejected\n"; }
}
int main() {
	auto a = scpp::create<record>(10), b = scpp::create<record>(20);
	Storage<record> list(4);
	std::cout << list.append(a) << ',' << list.append(b) << ',' << list.append(a) << '\n';
	auto alias = list;
	auto retained = list.read(0);
	alias.read(0)->value = 11;
	alias.replace(0, b);
	list.remove(1); list.unset(-1); list.unset(80); list.reserve(100);
	std::cout << list.append(a) << ':' << list.count() << ':' << list.is_empty() << ':' << list.contains(-1) << '\n';
	list.for_each([](auto k, auto r) { std::cout << k << '=' << r->value << '\n'; });
	std::cout << "retained=" << retained->value << '\n';
	rejection<std::out_of_range>([&] { (void)list.read(1); });
	rejection<std::out_of_range>([&] { list.replace(1, a); });
	rejection<std::out_of_range>([&] { list.remove(1); });
	rejection<std::invalid_argument>([&] { list.append({}); });
	rejection<std::invalid_argument>([] { Storage<record> bad(-1); });
	Keyed_Storage<record> map(4);
	for (const std::string &k : {std::string(""), std::string("0"), std::string("00"), std::string("a\0b", 3), std::string("a")}) map.add(k, a);
	map.set("0", b); map.replace("a", b); map.remove(""); map.set("", a);
	map.unset("absent"); map.reserve(100);
	map.for_each([](auto key, auto r) {
		for (unsigned char c : key) std::cout << std::hex << std::setw(2) << std::setfill('0') << unsigned(c);
		std::cout << std::dec << '=' << r->value << '\n';
	});
	std::cout << map.count() << ':' << map.is_empty() << ':' << map.contains("absent") << '\n';
	rejection<std::invalid_argument>([&] { map.add("0", a); });
	rejection<std::out_of_range>([&] { map.replace("absent", a); });
	rejection<std::out_of_range>([&] { map.remove("absent"); });
	rejection<std::out_of_range>([&] { (void)map.read("absent"); });
	rejection<std::invalid_argument>([&] { map.set("absent", {}); });
	rejection<std::invalid_argument>([] { Keyed_Storage<record> bad(-1); });
}
