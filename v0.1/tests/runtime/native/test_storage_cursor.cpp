#include "modules/compiler/storage_cursor.hpp"
#include "scpp/memory.hpp"
#include <cassert>

struct base { virtual ~base() = default; int value; explicit base(int n) : value(n) {} };
struct child : base { using base::base; };

int main() {
	using scpp::compiler::Storage;
	using scpp::compiler::Storage_Cursor;
	static_assert(!std::is_constructible_v<Storage_Cursor<child>, Storage<base>>);
	Storage<child> rows;
	rows.append(scpp::create<child>(3));
	rows.append(scpp::create<child>(5));
	rows.append(scpp::create<child>(7));
	rows.remove(1);
	Storage_Cursor<base> cursor(rows);
	assert(static_cast<bool>(cursor.valid()));
	assert(cursor.current()->value == 3);
	auto alias = cursor;
	alias.next();
	assert(cursor.current()->value == 7);
	assert(cursor.key().native_value() == 2);
	bool rejected = false;
	try { cursor.rewind(); } catch (const std::logic_error &) { rejected = true; }
	assert(rejected);
	cursor.next();
	assert(!static_cast<bool>(alias.valid()));
	cursor.next();
	Storage_Cursor<base> fresh(rows);
	assert(fresh.current()->value == 3);
	Storage_Cursor<base> retained = [] {
		Storage<child> temporary;
		temporary.append(scpp::create<child>(11));
		return Storage_Cursor<base>(temporary);
	}();
	assert(retained.current()->value == 11);
}
