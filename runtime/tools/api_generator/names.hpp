#pragma once
#include "value.hpp"
#include <set>

namespace scpp::api_generation
{
	struct type_name final
	{
		std::string name;
		std::vector<type_name> arguments;
		std::string canonical() const;
	};
	type_name parse_type(const std::string &text);
	std::string structural_name(const type_name &type);
	void authored_name(const std::string &name);
	class naming final
	{
		value registry_;
		std::map<std::string, type_name> aliases_;
		std::map<std::string, std::string> mappings_, claimed_;
		type_name structural_tree(type_name type) const;
	public:
		explicit naming(const value &registry);
		type_name normalize(type_name type) const;
		std::pair<std::string, std::string> resolve(const std::string &text);
	};
}
