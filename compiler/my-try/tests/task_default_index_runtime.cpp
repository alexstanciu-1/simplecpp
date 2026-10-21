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
	tasks::shutdown();
}
