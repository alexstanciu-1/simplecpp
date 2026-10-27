#pragma once
#include "value.hpp"
#include <set>

namespace scpp::api_generation
{
	struct type_name final
	{
		std::string name;
		std::vector<type_name> arguments;
		bool application = false;
		std::string canonical() const;
	};
	// Parameter order is preserved for dependency scheduling and dependent defaults.
	struct family_application final
	{
		type_name type;
		std::vector<std::pair<std::string, type_name>> arguments;
	};
	// Shared metadata adapter for physical generation and operation planning.
	value metadata_naming_registry(const value &metadata, const value &leaves, const value &names);
	type_name parse_type(const std::string &text);
	std::string structural_name(const type_name &type);
	void authored_name(const std::string &name);
	class naming final
	{
		value registry_;
		std::map<std::string, type_name> aliases_;
		std::map<std::string, std::string> mappings_, claimed_;
		type_name structural_tree(type_name type) const;
		type_name normalize_at(type_name type, unsigned depth) const;
	public:
		explicit naming(const value &registry);
		type_name normalize(type_name type) const;
		family_application bind(const std::string &text) const;
		std::pair<std::string, std::string> resolve(const std::string &text);
	};
}
