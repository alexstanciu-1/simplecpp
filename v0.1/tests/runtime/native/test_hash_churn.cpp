#include "test_common.hpp"
#include <algorithm>

// Live membership stays small while physical positions cross both narrow-index
// limits. This is the pattern used by a continuously refilled process pool.
int main() {
	scpp::hash_t<scpp::int_t<>, scpp::int_t<>> typed;
	scpp::hash_t dynamic;
	for (int i = 0; i < 66000; ++i) {
		const scpp::int_t<> key{i + 100};
		typed.set(key, scpp::int_t<>{i});
		dynamic.set(key, scpp::mixed_t{scpp::int_t<>{i}});
		if (i >= 16) {
			assert(typed.remove(scpp::int_t<>{i + 84}));
			assert(dynamic.remove(scpp::int_t<>{i + 84}));
		}
		assert(typed.at(key).native_value() == i);
		assert(dynamic.at(key).int_value().native_value() == i);
		assert(typed.size() == static_cast<std::size_t>(std::min(i + 1, 16)));
		assert(dynamic.size() == typed.size());
	}
	for (int i = 65984; i < 66000; ++i) {
		assert(typed.at(scpp::int_t<>{i + 100}).native_value() == i);
		assert(dynamic.at(scpp::int_t<>{i + 100}).int_value().native_value() == i);
	}
	int expected = 65984;
	for (auto it = typed.begin_entries(); it != typed.end_entries(); ++it) {
		assert((*it).key().native_value() == expected + 100);
		assert((*it).value_ref().native_value() == expected++);
	}
	assert(expected == 66000);
	expected = 65984;
	for (auto it = dynamic.begin_entries(); it != dynamic.end_entries(); ++it) {
		assert((*it).key().int_value().native_value() == expected + 100);
		assert((*it).value_ref().int_value().native_value() == expected++);
	}
	assert(expected == 66000);
	for (int i = 65984; i < 66000; ++i) {
		assert(typed.remove(scpp::int_t<>{i + 100}));
		assert(!typed.has(scpp::int_t<>{i + 100}).native_value());
		assert(dynamic.remove(scpp::int_t<>{i + 100}));
		assert(!dynamic.has(scpp::int_t<>{i + 100}).native_value());
	}
	assert(typed.empty().native_value());
	assert(dynamic.empty().native_value());
}
