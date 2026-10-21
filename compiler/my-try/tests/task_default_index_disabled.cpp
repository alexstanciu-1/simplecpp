#include "modules/tasks/tasks.hpp"
#include <cassert>
using namespace scpp;
int main() {
	vector_t<int_t<>> items;
	hash_t<int_t<>, string_t> keyed;
	auto work = [](int_t<> n) { return n; };
	auto index = [](int_t<>) { return string_t("key"); };
	int rejected = 0;
	try { (void)tasks::start(items, int_t<>(1), work, index, null, null, int_t<>(0)); }
	catch (const runtime_error &e) { assert(std::string(e.what()).find("not enabled") != std::string::npos); ++rejected; }
	try { (void)tasks::start(keyed, int_t<>(1), work, index, null, null, int_t<>(0)); }
	catch (const runtime_error &e) { assert(std::string(e.what()).find("not enabled") != std::string::npos); ++rejected; }
	auto error=[](int_t<>,shared_p<tasks::error>) {};
	try { (void)tasks::run(items, int_t<>(1), work, index, null, error, int_t<>(0)); }
	catch (const runtime_error &e) { assert(std::string(e.what()).find("not enabled") != std::string::npos); ++rejected; }
	try { (void)tasks::run(keyed, int_t<>(1), work, index, null, error, int_t<>(0)); }
	catch (const runtime_error &e) { assert(std::string(e.what()).find("not enabled") != std::string::npos); ++rejected; }
	assert(rejected == 4);
}
