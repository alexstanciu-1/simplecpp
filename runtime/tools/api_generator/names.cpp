#include "names.hpp"
#include <cctype>
#include <functional>

namespace scpp::api_generation
{
	void authored_name(const std::string &name)
	{
		std::size_t start = 0;
		do {
			auto end = name.find("::", start);
			auto part = name.substr(start, end == std::string::npos ? end : end-start);
			if (part.empty() || !std::isalpha(static_cast<unsigned char>(part[0])) || part.back() == '_' || part.find("__") != std::string::npos)
				throw std::runtime_error("Invalid authored/registered name: " + name);
			for (unsigned char c : part) if (!std::isalnum(c) && c != '_') throw std::runtime_error("Invalid name: " + name);
			if (end == std::string::npos) break;
			start = end + 2;
		} while (true);
	}
	std::string type_name::canonical() const
	{
		std::string out = this->name;
		if (!this->arguments.empty() || this->application) {
			out += '<';
			for (std::size_t i=0; i<this->arguments.size(); ++i) { if (i) out += ','; out += this->arguments[i].canonical(); }
			out += '>';
		}
		return out;
	}
	type_name parse_type(const std::string &text)
	{
		std::size_t pos = 0;
		auto whitespace = [&] { while (pos < text.size() && std::isspace(static_cast<unsigned char>(text[pos]))) ++pos; };
		std::function<type_name(unsigned)> read = [&](unsigned depth) {
			if (depth > 128) throw std::runtime_error("Type nesting limit exceeded");
			whitespace(); auto start = pos;
			while (pos < text.size() && (std::isalnum(static_cast<unsigned char>(text[pos])) || text[pos]=='_' || text[pos]==':')) ++pos;
			type_name node{text.substr(start, pos-start), {}};
			if (node.name.empty()) throw std::runtime_error("Expected type");
			bool number = std::isdigit(static_cast<unsigned char>(node.name[0]));
			if (!number) authored_name(node.name);
			else {
                for (unsigned char c : node.name) if (!std::isdigit(c)) throw std::runtime_error("Invalid value argument");
                auto first=node.name.find_first_not_of('0');
                node.name=first==std::string::npos ? "0" : node.name.substr(first);
            }
			whitespace();
			if (pos < text.size() && text[pos]=='<') {
				if (number) throw std::runtime_error("Invalid type");
				++pos;
                node.application = true;
                whitespace();
                if (pos < text.size() && text[pos] == '>') { ++pos; return node; }
				do {
					node.arguments.push_back(read(depth+1)); whitespace();
					if (pos == text.size() || (text[pos]!=',' && text[pos]!='>')) throw std::runtime_error("Expected ',' or '>'");
				} while (text[pos++] == ',');
			}
			return node;
		};
		auto out = read(0); whitespace();
		if (pos != text.size()) throw std::runtime_error("Unexpected trailing type tokens");
		return out;
	}
	static std::string escape(const std::string &name)
	{
		authored_name(name); std::string out;
		for (char c : name) out += c=='z' ? "zZ" : c=='_' ? "zU" : c==':' ? "zC" : std::string(1,c);
		return out;
	}
	std::string structural_name(const type_name &type)
	{
		std::string out = escape(type.name);
		std::function<void(const type_name &, unsigned)> visit = [&](const type_name &node, unsigned level) {
			out += "_" + std::to_string(level) + escape(node.name);
			for (const auto &child : node.arguments) visit(child, level+1);
		};
		for (const auto &child : type.arguments) visit(child, 0);
		return out + '_';
	}
	/** Substitute complete type nodes, never substrings or arbitrary C++ text. */
	static type_name substitute_default(type_name type, const std::map<std::string, type_name> &arguments)
	{
		if (arguments.contains(type.name)) {
			if (!type.arguments.empty() || type.application) {
				throw std::runtime_error("Default parameter cannot be used as a template family: " + type.name);
			}
			return arguments.at(type.name);
		}
		for (auto &argument : type.arguments) argument = substitute_default(argument, arguments);
		return type;
	}

	/** Validate default declarations even when a request explicitly supplies every argument. */
	static void validate_family(const std::string &name, const value &family)
	{
		authored_name(name);
		const auto &parameters = family.at("parameters");
		if (parameters.type != value::kind::array) throw std::runtime_error("parameters must be an array: " + name);
		std::map<std::string, std::size_t> positions;
		if (family.has("parameter_names")) {
			const auto &names = family.at("parameter_names");
			if (names.type != value::kind::array || names.items.size() != parameters.items.size()) {
				throw std::runtime_error("Parameter name count mismatch: " + name);
			}
			for (std::size_t i = 0; i < names.items.size(); ++i) {
				auto parameter = names.items[i].text();
				authored_name(parameter);
				if (!positions.emplace(parameter, i).second) throw std::runtime_error("Duplicate parameter: " + parameter);
			}
		}
		for (const auto &parameter : parameters.items) {
			if (parameter.text() != "type" && parameter.text() != "integer") throw std::runtime_error("Unknown parameter kind");
		}
		if (!family.has("defaults")) return;
		const auto &defaults = family.at("defaults");
		if (defaults.type != value::kind::array || defaults.items.size() > parameters.items.size()) {
			throw std::runtime_error("Invalid trailing defaults: " + name);
		}
		for (std::size_t i = 0; i < defaults.items.size(); ++i) {
			const auto target = parameters.items.size() - defaults.items.size() + i;
			std::function<void(const type_name &)> check = [&](const type_name &node) {
				if (positions.contains(node.name)) {
					if (positions.at(node.name) >= target) throw std::runtime_error("Default must reference an earlier parameter: " + node.name);
					if (!node.arguments.empty() || node.application) throw std::runtime_error("Default parameter cannot be used as a template family: " + node.name);
				}
				for (const auto &child : node.arguments) check(child);
			};
			check(parse_type(defaults.items[i].text()));
		}
	}

	naming::naming(const value &registry) : registry_(registry)
	{
		if (!registry.has("version") || registry.at("version").scalar!="1") throw std::runtime_error("Unsupported naming registry version");
		for (const auto &t : registry.at("types").items) authored_name(t.text());
		for (const auto &[name, f] : registry.at("families").fields) validate_family(name, f);
		const std::string keywords = " alignas alignof and and_eq asm auto bitand bitor bool break case catch char char8_t char16_t char32_t class compl concept const consteval constexpr constinit const_cast continue co_await co_return co_yield decltype default delete do double dynamic_cast else enum explicit export extern false float for friend goto if inline int long mutable namespace new noexcept not not_eq nullptr operator or or_eq private protected public register reinterpret_cast requires return short signed sizeof static static_assert static_cast struct switch template this thread_local throw true try typedef typeid typename union unsigned using virtual void volatile wchar_t while xor xor_eq ";
		for (const auto &m : registry.at("mappings").items) {
			auto name = m.at("name").text(); authored_name(name);
			if (name.find("::") != std::string::npos || keywords.find(" "+name+" ") != std::string::npos || registry.at("families").has(name))
				throw std::runtime_error("Conflicting concrete name: " + name);
			auto type = this->normalize(parse_type(m.at("type").text())); auto key = type.canonical();
			for (const auto &t : registry.at("types").items) if (t.text()==name && key!=name) throw std::runtime_error("Conflicting leaf name: "+name);
			if (this->aliases_.contains(name) && this->aliases_.at(name).canonical() != key) {
                throw std::runtime_error("Conflicting concrete name: " + name);
            }
            if (this->mappings_.contains(key)) {
                if (this->mappings_.at(key) != name) throw std::runtime_error("Conflicting mapping: " + key);
                continue; // Explicit and defaulted spellings can repeat the same binding.
            }
			this->mappings_[key]=name; this->aliases_[name]=type;
		}
	}
	type_name naming::normalize(type_name type) const
	{
		return this->normalize_at(std::move(type), 0);
	}

	/** Resolve left to right so dependent defaults see normalized earlier arguments. */
	type_name naming::normalize_at(type_name type, unsigned depth) const
	{
		if (depth > 128) throw std::runtime_error("Default/type expansion limit exceeded");
		if (type.arguments.empty() && !type.application) {
			if (this->aliases_.contains(type.name)) return this->aliases_.at(type.name);
			for (const auto &t : this->registry_.at("types").items) if (t.text() == type.name) return type;
		}
		const auto &families = this->registry_.at("families");
		if (!families.has(type.name)) throw std::runtime_error("Unregistered type or template family: " + type.name);
		const auto &family = families.at(type.name);
		const auto &parameters = family.at("parameters").items;
		const auto defaults = family.has("defaults") ? family.at("defaults").items : std::vector<value>{};
		const auto required = parameters.size() - defaults.size();
		if (type.arguments.size() > parameters.size() || type.arguments.size() < required) {
			throw std::runtime_error("Wrong argument count for " + type.name);
		}
		std::map<std::string, type_name> resolved;
		for (std::size_t i = 0; i < parameters.size(); ++i) {
			if (i == type.arguments.size()) {
				auto argument = parse_type(defaults.at(i - required).text());
				type.arguments.push_back(substitute_default(std::move(argument), resolved));
			}
			if (parameters[i].text() == "type") {
				type.arguments[i] = this->normalize_at(std::move(type.arguments[i]), depth + 1);
			} else {
				bool valid = false;
				for (const auto &v : family.at("values").items) if (v.scalar == type.arguments[i].canonical()) valid = true;
				if (!valid) throw std::runtime_error("Invalid value argument for " + type.name);
			}
			if (family.has("parameter_names")) resolved[family.at("parameter_names").items[i].text()] = type.arguments[i];
		}
		type.application = false;
		return type;
	}
	type_name naming::structural_tree(type_name type) const
	{
		for (const auto &arg : type.arguments) if (std::isdigit(static_cast<unsigned char>(arg.name[0]))) {
			auto found=this->mappings_.find(type.canonical());
			if (found==this->mappings_.end()) throw std::runtime_error("Non-type arguments require an explicit concrete mapping");
			return type_name{found->second,{}};
		}
		for (auto &arg : type.arguments) arg=this->structural_tree(arg);
		return type;
	}
	std::pair<std::string,std::string> naming::resolve(const std::string &text)
	{
		auto type=this->normalize(parse_type(text)); auto key=type.canonical();
		auto name=this->mappings_.contains(key) ? this->mappings_.at(key) : type.arguments.empty() ? type.name : structural_name(this->structural_tree(type));
		if (this->claimed_.contains(name) && this->claimed_.at(name)!=key) throw std::runtime_error("Concrete name collision: "+name);
		this->claimed_[name]=key; return {key,name};
	}
}
