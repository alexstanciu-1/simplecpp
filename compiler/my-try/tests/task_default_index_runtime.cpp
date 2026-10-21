#include "modules/tasks/tasks.hpp"
#include <cassert>
using namespace scpp;
using integer = int_t<>;
int main() {
	vector_t<integer> items;items.append(integer(40));items.append(integer(42));
	auto work=[](integer n){return n;};
	auto key=[](integer){return string_t("same");};
	auto values=tasks::run(items,integer(2),work,key,null,null,integer(0));
	assert(values.size()==1 && values[string_t("same")].native_value()==42);
	auto batch=tasks::start(items,integer(2),work,key,null,null,integer(0));
	(void)tasks::join(batch);
	hash_t<integer,string_t> keyed;keyed.set(string_t("a"),integer(40));keyed.set(string_t("b"),integer(42));
	auto hash_batch=tasks::start(keyed,integer(2),work,key,null,null,integer(0));
	(void)tasks::join(hash_batch);
	auto fail_first=[](integer n) -> integer { if(n.native_value()==40){throw std::runtime_error("omit");}return n; };
	auto omit=[](integer,shared_p<tasks::error>) {};
	auto recovered=tasks::run(items,integer(2),fail_first,key,null,omit,integer(0));
	assert(recovered.size()==1 && recovered[string_t("same")].native_value()==42);
	auto recovered_hash=tasks::run(keyed,integer(2),fail_first,key,null,omit,integer(0));
	assert(recovered_hash.size()==1 && recovered_hash[string_t("same")].native_value()==42);
	auto nullable_index=[](integer n) { return n.native_value()==40 ? nullable<integer>(integer(7)) : nullable<integer>(null); };
	auto nullable_result=tasks::run(items,integer(1),work,nullable_index,null,null,integer(0));
	assert(nullable_result[integer(7)].native_value()==40 && nullable_result[integer(8)].native_value()==42);
	auto nullable_batch=tasks::start(items,integer(1),work,nullable_index,null,null,integer(0));
	(void)tasks::join(nullable_batch);
	auto nullable_string=[](integer) { return nullable<string_t>(string_t("key")); };
	auto string_result=tasks::run(items,integer(1),work,nullable_string,null,null,integer(0));
	assert(string_result[string_t("key")].native_value()==42);
	bool rejected_append=false;
	try { (void)tasks::run(items,integer(1),work,[](integer){return nullable<string_t>(null);},null,null,integer(0)); }
	catch(const std::runtime_error &e) { rejected_append=std::string(e.what()).find("requires integer keys")!=std::string::npos; }
	assert(rejected_append);
	tasks::shutdown();
}
