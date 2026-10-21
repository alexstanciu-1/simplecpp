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
	auto nullable_index=[](int_t<>) { return nullable<int_t<>>(null); };
	try { (void)tasks::run(items, int_t<>(1), work, nullable_index, null, null, int_t<>(0)); }
	catch (const runtime_error &e) { assert(std::string(e.what()).find("not enabled") != std::string::npos); ++rejected; }
	shared_p<tasks::dynamic_batch> dynamic_handle;
	try { (void)tasks::join(dynamic_handle); }
	catch (const runtime_error &e) { assert(std::string(e.what()).find("not enabled") != std::string::npos); ++rejected; }
	auto dynamic_input=dynamic_t<>::make_container();
	try { (void)tasks::run(dynamic_input,int_t<>(1),[](dynamic_t<> value){return value;}); }
	catch (const runtime_error &e) { assert(std::string(e.what()).find("not enabled") != std::string::npos); ++rejected; }
	try { (void)tasks::start(dynamic_input,int_t<>(1),[](dynamic_t<> value){return value;}); }
	catch (const runtime_error &e) { assert(std::string(e.what()).find("not enabled") != std::string::npos); ++rejected; }
	assert(rejected == 8);
}
