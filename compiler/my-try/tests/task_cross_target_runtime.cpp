#include "modules/tasks/tasks.hpp"
#include <cassert>
using namespace scpp;
using integer = int_t<>;
int main() {
	vector_t<integer> items; items.append(integer(0)); items.append(integer(1));
	auto work=[](integer n) { return integer(n.native_value()+40); };
	hash_t<integer,integer> target; target.set(integer(7),integer(9));
	auto appended=tasks::run(items,integer(2),work,null,target,null,integer(0));
	assert(appended[integer(7)].native_value()==9 && appended[integer(8)].native_value()==40 && appended[integer(9)].native_value()==41);
	auto key=[](integer n) { return n; };
	auto keyed=tasks::run(items,integer(2),work,key,target,null,integer(0));
	assert(keyed[integer(0)].native_value()==40 && keyed[integer(1)].native_value()==41 && keyed[integer(7)].native_value()==9);
	auto nullable_key=[](integer) { return nullable<integer>(null); };
	auto nullable_keys=tasks::run(items,integer(2),work,nullable_key,target,null,integer(0));
	assert(nullable_keys[integer(8)].native_value()==40 && nullable_keys[integer(9)].native_value()==41);
	hash_t<integer,integer> input; input.set(integer(0),integer(0)); input.set(integer(1),integer(1));
	vector_t<integer> vector_target; vector_target.append(integer(9));
	auto vector_result=tasks::run(input,integer(1),work,null,vector_target,null,integer(0));
	assert(vector_result.size()==2 && vector_result[0].native_value()==40 && vector_result[1].native_value()==41);
	auto fail=[](integer n)->integer { if(n.native_value()==0) throw std::runtime_error("failed"); return integer(41); };
	auto replace=[](integer,shared_p<tasks::error>) { return integer(42); };
	auto recovered=tasks::run(input,integer(1),fail,key,vector_target,replace,integer(0));
	assert(recovered[0].native_value()==42 && recovered[1].native_value()==41);
	auto background=tasks::start(items,integer(2),work,key,target,null,integer(0));
	(void)tasks::join(background);
	auto reverse=tasks::start(input,integer(1),work,null,vector_target,null,integer(0));
	(void)tasks::join(reverse);
	bool sparse=false;
	try { (void)tasks::run(input,integer(1),work,[](integer){return integer(9);},vector_target,null,integer(0)); }
	catch(const runtime_error &) { sparse=true; }
	assert(sparse);
	assert(target.size()==1 && vector_target.size()==1 && vector_target[0].native_value()==9);
	hash_t<integer,string_t> strings; strings.set(string_t("old"),integer(7));
	auto string_key=[](integer n) { return n.native_value()==0 ? string_t("a") : string_t("b"); };
	auto string_result=tasks::run(items,integer(2),work,string_key,strings,null,integer(0));
	assert(string_result[string_t("a")].native_value()==40 && string_result[string_t("old")].native_value()==7);
	hash_t<integer,string_t> string_input; string_input.set(string_t("a"),integer(0)); string_input.set(string_t("b"),integer(1));
	auto rekeyed=tasks::run(string_input,integer(1),work,key,target,null,integer(0));
	assert(rekeyed[integer(0)].native_value()==40 && rekeyed[integer(1)].native_value()==41);
	auto vector_from_strings=tasks::run(string_input,integer(1),work,key,vector_target,null,integer(0));
	assert(vector_from_strings[0].native_value()==40 && vector_from_strings[1].native_value()==41);
	auto string_batch=tasks::start(items,integer(2),work,string_key,strings,null,integer(0));
	(void)tasks::join(string_batch);
	auto rekeyed_batch=tasks::start(string_input,integer(1),work,key,target,null,integer(0));
	(void)tasks::join(rekeyed_batch);
	tasks::shutdown();
}
