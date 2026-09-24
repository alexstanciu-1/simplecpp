#include "storage_test_support.hpp"

int main() {
	assert(record::alive == 0);
	scpp::shared_p<record> retained;
	{
		auto a = scpp::create<record>(10);
		auto b = scpp::create<record>(20);
		Storage<record> list;
		Keyed_Storage<record> map;
		list.append(a); list.append(a);
		map.add("a", a); map.add("also-a", a);
		assert(record::alive == 2);
		retained = list.read(0);
		list.read(0)->value = 30;
		assert(map.read("a")->value == 30 && list.read(1)->value == 30);
		Storage<record> alias;
		alias = list;
		auto keyed_alias = map;
		alias.replace(0, b);
		keyed_alias.replace("a", b);
		assert(list.read(0).get() == b.get() && map.read("a").get() == b.get());
		assert(retained.get() == a.get() && retained->value == 30);
		Storage<record> independent;
		Keyed_Storage<record> independent_map;
		independent.append(a); independent_map.add("a", a);
		alias.remove(1); keyed_alias.remove("also-a");
		assert(list.count() == 1 && map.count() == 1);
		assert(independent.count() == 1 && independent_map.count() == 1);
		for (int i = 0; i < 1000; ++i) { list.append(b); map.add(std::to_string(i), b); }
		assert(retained.get() == a.get());
		// Move syntax also aliases; it does not invalidate the source collection.
		auto moved = std::move(alias);
		moved.read(0)->value = 40;
		assert(alias.read(0)->value == 40);
	}
	assert(record::alive == 1 && record::destroyed == 1 && retained->value == 30);
	retained.reset();
	assert(record::alive == 0 && record::destroyed == 2);
}
