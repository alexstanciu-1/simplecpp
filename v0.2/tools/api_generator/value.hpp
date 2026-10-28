#pragma once
#include <map>
#include <string>
#include <vector>
#include <stdexcept>

namespace scpp::api_generation
{
	// Tool-owned metadata tree, decoded by the independent native JSON reader.
	struct value final
	{
		enum class kind { null, text, number, boolean, array, object } type = kind::null;
		std::string scalar;
		std::vector<value> items;
		std::map<std::string, value> fields;
		value() = default;
		value(const std::string &s) : type(kind::text), scalar(s) {}
		value(const char *s) : value(std::string(s)) {}
		static value object() { value v; v.type = kind::object; return v; }
		static value array() { value v; v.type = kind::array; return v; }
		bool has(const std::string &key) const;
		const value &at(const std::string &key) const;
		std::string text() const;
		std::string get(const std::string &key, const std::string &fallback = "") const;
		bool yes(const std::string &key) const;
		value &operator[](const std::string &key);
	};
	value parse_json(const std::string &text, const std::string &file = "<api metadata>");
	std::string json_text(const value &v);
	void fields_only(const value &v, const std::string &allowed, const std::string &where);
}
