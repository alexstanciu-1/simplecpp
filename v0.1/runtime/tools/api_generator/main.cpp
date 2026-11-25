#include "scpp/api_generation.hpp"
#include "scpp/detail/string_access.hpp"
#include <iostream>
#include <iterator>

int main()
{
	try {
		std::string input(std::istreambuf_iterator<char>(std::cin), {});
		auto result = scpp::api_generation::generate(scpp::string(input.data(), scpp::uint64(input.size())));
		std::cout << scpp::detail::string_access::get(result).native_value() << '\n';
		return 0;
	} catch (const std::exception &error) {
		std::cerr << "API generation: " << error.what() << '\n'; return 1;
	}
}
