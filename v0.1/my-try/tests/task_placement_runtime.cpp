#include "modules/tasks/tasks.hpp"
#include <iostream>
#include <cassert>
using namespace scpp;
using integer = int_t<>;
int main() {
	vector_t<integer> items; items.append(integer(0)); items.append(integer(1));
	auto work = [](integer n) -> integer {
		if (n.native_value() == 0) { throw std::runtime_error("omit"); }
		return integer(42);
	};
	auto omit = [](integer, shared_p<tasks::error>) {};
	const auto coordinator = std::this_thread::get_id();
	std::int64_t indexed_item = -1;
	auto index = [&](integer n) {
		assert(std::this_thread::get_id() == coordinator);
		indexed_item = n.native_value(); return n;
	};
	hash_t<integer,integer> target;
	auto result = tasks::run(items,integer(1),work,index,target,omit,integer(0));
	std::cout << "{\"indexed_item\":" << indexed_item << ",\"result_count\":" << result.size() << "}\n";
	assert(result.has(integer(1)).native_value() && !result.has(integer(0)).native_value());
	hash_t<integer,integer> keyed; keyed.set(integer(10),integer(0)); keyed.set(integer(11),integer(1));
	auto kept=tasks::run(keyed,integer(1),work,index,target,omit,integer(0));
	assert(kept.size()==1 && kept.has(integer(1)).native_value());
	auto background_index=[](integer n){assert(n.native_value()==1);return n;};
	auto background=tasks::start(items,integer(1),work,background_index,target,omit,integer(0));
	(void)tasks::join(background);
	auto background_hash=tasks::start(keyed,integer(1),work,background_index,target,omit,integer(0));
	(void)tasks::join(background_hash);
	vector_t<integer> filled; filled.append(integer(7));filled.append(integer(8));
	auto overwritten=tasks::run(items,integer(1),[](integer n){return integer(n.native_value()+40);},
		[](integer){return integer(0);},filled,null,integer(0));
	assert(overwritten.size()==2 && overwritten.at(0).native_value()==41 && overwritten.at(1).native_value()==8);
	auto appended=tasks::run(items,integer(1),[](integer n){return integer(n.native_value()+40);},
		[](integer) -> nullable<integer> {return null;},filled,null,integer(0));
	assert(appended.size()==4 && appended.at(0).native_value()==7 && appended.at(3).native_value()==41);
	for (int invalid : {-1,3}) {
		bool rejected=false;
		try { (void)tasks::run(items,integer(1),[](integer n){return n;},
			[invalid](integer){return integer(invalid);},filled,null,integer(0)); }
		catch (const std::exception&) { rejected=true; }
		assert(rejected);
	}
	tasks::shutdown();
}
