#include "value.hpp"
#include "json/pull_reader.hpp"
#include <sstream>
#include <iomanip>
#include <locale>

namespace scpp::api_generation
{
	bool value::has(const std::string &key) const
	{
		if (this->type != kind::object) throw std::runtime_error("Expected metadata object");
		return this->fields.contains(key);
	}
	value &value::operator[](const std::string &key)
	{
		if (this->type == kind::null) this->type = kind::object;
		if (this->type != kind::object) throw std::runtime_error("Expected metadata object");
		return this->fields[key];
	}
	const value &value::at(const std::string &key) const
	{
		if (this->type != kind::object) throw std::runtime_error("Expected metadata object");
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
		if (v.scalar != "true" && v.scalar != "false") throw std::runtime_error("Invalid metadata boolean: " + key);
		return v.scalar == "true";
	}
	static value read_value(tools::json::pull_reader &reader)
	{
		value out;
		auto kind = reader.kind();
		if (kind == "object") {
			out = value::object(); reader.begin_object();
			while (reader.next_property()) {
				auto key = reader.property_name();
				out.fields.emplace(std::move(key), read_value(reader));
			}
		} else if (kind == "array") {
			out = value::array(); reader.begin_array();
			while (reader.next_element()) out.items.push_back(read_value(reader));
		} else if (kind == "string") out = value(reader.read_string());
		else if (kind == "number") { out.type = value::kind::number; out.scalar = reader.read_number(); }
		else if (kind == "boolean") { out.type = value::kind::boolean; out.scalar = reader.read_boolean() ? "true" : "false"; }
		else reader.read_null();
		return out;
	}
	value parse_json(const std::string &text, const std::string &file)
	{
		tools::json::pull_reader reader(text, file);
		auto out = read_value(reader); reader.finish_document(); return out;
	}
	static std::string quote(const std::string &s)
	{
		try { tools::json::reader_detail::validate_utf8(s); }
		catch (const tools::json::reader_detail::json_parse_failure &failure) {
			throw tools::json::read_error(failure, "<metadata output>", "$");
		}
		std::ostringstream out; out.imbue(std::locale::classic()); out << '"';
		for (unsigned char c : s) {
			if (c == '"' || c == '\\') out << '\\' << c;
			else if (c < 32) out << "\\u" << std::hex << std::setw(4) << std::setfill('0') << unsigned(c);
			else out << c;
		}
		out << '"'; return out.str();
	}
	static std::string write_value(const value &v, unsigned depth)
	{
		if (v.type != value::kind::object && !v.fields.empty()) throw std::runtime_error("Unexpected metadata object fields");
		if (v.type != value::kind::array && !v.items.empty()) throw std::runtime_error("Unexpected metadata array items");
		if ((v.type == value::kind::object || v.type == value::kind::array || v.type == value::kind::null)
			&& !v.scalar.empty()) throw std::runtime_error("Unexpected metadata scalar");
		if (v.type == value::kind::null) return "null";
		if (v.type == value::kind::text) return quote(v.scalar);
		if (v.type == value::kind::boolean) {
			if (v.scalar != "true" && v.scalar != "false") throw std::runtime_error("Invalid metadata boolean");
			return v.scalar;
		}
		if (v.type == value::kind::number) {
			tools::json::pull_reader reader(v.scalar, "<metadata number>");
			const auto token = reader.read_number(); reader.finish_document();
			if (token != v.scalar) throw std::runtime_error("Invalid metadata number token");
			return token;
		}
		if (v.type != value::kind::array && v.type != value::kind::object) throw std::runtime_error("Invalid metadata kind");
		if (depth >= 128) throw std::runtime_error("Metadata output nesting limit exceeded");
		std::string out = v.type == value::kind::array ? "[" : "{";
		bool first = true;
		for (const auto &item : v.items) { if (!first) out += ','; first = false; out += write_value(item, depth + 1); }
		for (const auto &[key, item] : v.fields) { if (!first) out += ','; first = false; out += quote(key) + ':' + write_value(item, depth + 1); }
		return out + (v.type == value::kind::array ? "]" : "}");
	}
	std::string json_text(const value &v) { return write_value(v, 0); }
	void fields_only(const value &v, const std::string &allowed, const std::string &where)
	{
		if (v.type != value::kind::object) throw std::runtime_error(where + ": expected object");
		for (const auto &[key, item] : v.fields)
			if ((" " + allowed + " ").find(" " + key + " ") == std::string::npos)
				throw std::runtime_error(where + ": unknown field " + key);
	}
}
