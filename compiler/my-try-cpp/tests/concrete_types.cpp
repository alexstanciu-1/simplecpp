#include "scpp_compiler/string.hpp"
#include "scpp_compiler/vector_string.hpp"
#include "scpp_compiler/json_value.hpp"
#include "scpp_compiler/textbuilder.hpp"

/**
 * Prove signed collection arithmetic and checked JSON index boundaries.
 */
static bool signed_collection_contract()
{
	using namespace scpp::compiler_support;
	vector_string strings;
	vector_json nodes;
	json_value array = json_value::parse(string("[10,20]"));
	static_assert(__is_same(decltype(strings.size()), int64));
	static_assert(__is_same(decltype(nodes.size()), int64));
	static_assert(__is_same(decltype(array.size()), int64));
	if (strings.size() - 1 != -1 || nodes.size() != 0) {
		return false;
	}

	int64 total = 0;
	for (int64 i = array.size() - 1; i >= 0; --i) {
		nodes.append(array.at(i));
		total += nodes.read(nodes.size() - 1).integer();
	}
	if (total != 30 || nodes.size() != 2) {
		return false;
	}

	int64 rejected = 0;
	for (int64 i = -1; i <= array.size(); i += array.size() + 1)
	{
		try {
			(void)array.at(i);
		}
		catch (...) {
			++rejected;
		}
		try {
			(void)nodes.read(i);
		}
		catch (...) {
			++rejected;
		}
	}
	try {
		strings.reserve(-1);
	}
	catch (...) {
		++rejected;
	}

	textbuilder report;
	report.append_number(array.size() - 3);
	return rejected == 5 && report.equals("-1");
}

/**
 * Check value-copy isolation, retained lifetimes and vector bounds across the middleware boundary.
 */
int main()
{
	using scpp::compiler_support::string;
	using scpp::compiler_support::vector_string;

	string token("name");
	if (!token.is_one_of("name type") || !token.is_one_of(" type  name ") ||
		token.is_one_of("names typename") || token.is_one_of("") ||
		string("").is_one_of(" ") || string("na\0me", 5).is_one_of("name")) {
		return 8;
	}

	string bytes("a\0b", 3);
	if (bytes.byte_size() != 3 || bytes.equals(string("a"))) {
		return 1;
	}

	string copy(bytes);
	bytes = string("changed");
	if (!copy.equals(string("a\0b", 3))) {
		return 2;
	}

	vector_string entries;
	entries.append(copy);
	string saved = entries.read(0);
	vector_string other(entries);
	entries.reserve(128);
	entries.append(string("second"));
	copy = string("modified input");
	if (other.size() != 1 || entries.size() != 2 || !saved.equals(string("a\0b", 3))) {
		return 3;
	}
	if (!entries.read(0).equals(saved) || !other.read(0).equals(saved)) {
		return 4;
	}

	{
		vector_string temporary;
		temporary.append(string("survives owner"));
		saved = temporary.read(0);
	}
	if (!saved.equals(string("survives owner"))) {
		return 5;
	}

	vector_string assigned;
	assigned = entries;
	entries.append(string("third"));
	if (assigned.size() != 2) {
		return 6;
	}

	bool negative_rejected = false;
	bool end_rejected = false;
	try {
		(void)entries.read(-1);
	}
	catch (...) {
		negative_rejected = true;
	}
	try {
		(void)entries.read(3);
	}
	catch (...) {
		end_rejected = true;
	}

	return negative_rejected && end_rejected && signed_collection_contract() ? 0 : 7;
}
