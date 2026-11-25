#include "value.hpp"
#include "modules/json/pull_reader.hpp"
#include <sstream>
#include <iomanip>

namespace scpp::api_generation
{
	const value &value::at(const std::string &key) const
	{
		auto found = this->fields.find(key);
		if (found == this->fields.end()) throw std::runtime_error("Missing metadata field: " + key);
		return found->second;
	}
	std::string value::text() const
	{
		if (this->type != kind::text) throw std::runtime_error("Expected metadata string");
		return this->scalar;
	}
	std::string value::get(const std::string &key, const std::string &fallback) const
	{
		return this->has(key) ? this->at(key).text() : fallback;
	}
	bool value::yes(const std::string &key) const
	{
		if (!this->has(key)) return false;
		const auto &v = this->at(key);
		if (v.type != kind::boolean) throw std::runtime_error("Expected metadata boolean: " + key);
		return v.scalar == "true";
	}
	static value read_value(json::pull_reader &reader)
	{
		value out;
		auto kind = reader.kind().native_value();
		if (kind == "object") {
			out = value::object(); reader.begin_object();
			while (reader.next_property().native_value()) {
				auto key = reader.property_name().native_value();
				out.fields.emplace(std::move(key), read_value(reader));
			}
		} else if (kind == "array") {
			out = value::array(); reader.begin_array();
			while (reader.next_element().native_value()) out.items.push_back(read_value(reader));
		} else if (kind == "string") out = value(reader.read_string().native_value());
		else if (kind == "number") { out.type = value::kind::number; out.scalar = reader.read_number().native_value(); }
		else if (kind == "boolean") { out.type = value::kind::boolean; out.scalar = reader.read_boolean().native_value() ? "true" : "false"; }
		else reader.read_null();
		return out;
	}
	value parse_json(const std::string &text)
	{
		json::pull_reader reader(string_t(text), string_t("<api metadata>"));
		auto out = read_value(reader); reader.finish_document(); return out;
	}
	static std::string quote(const std::string &s)
	{
		std::ostringstream out; out << '"';
		for (unsigned char c : s) {
			if (c == '"' || c == '\\') out << '\\' << c;
			else if (c < 32) out << "\\u" << std::hex << std::setw(4) << std::setfill('0') << unsigned(c);
			else out << c;
		}
		out << '"'; return out.str();
	}
	std::string json_text(const value &v)
	{
		if (v.type == value::kind::null) return "null";
		if (v.type == value::kind::text) return quote(v.scalar);
		if (v.type == value::kind::number || v.type == value::kind::boolean) return v.scalar;
		std::string out = v.type == value::kind::array ? "[" : "{";
		bool first = true;
		for (const auto &item : v.items) { if (!first) out += ','; first = false; out += json_text(item); }
		for (const auto &[key, item] : v.fields) { if (!first) out += ','; first = false; out += quote(key) + ':' + json_text(item); }
		return out + (v.type == value::kind::array ? "]" : "}");
	}
	void fields_only(const value &v, const std::string &allowed, const std::string &where)
	{
		if (v.type != value::kind::object) throw std::runtime_error(where + ": expected object");
		for (const auto &[key, item] : v.fields)
			if ((" " + allowed + " ").find(" " + key + " ") == std::string::npos)
				throw std::runtime_error(where + ": unknown field " + key);
	}
}
