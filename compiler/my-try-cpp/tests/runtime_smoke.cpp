#include "scpp_compiler/textbuilder.hpp"

/**
 * Prove builder output and compiled runtime string-pool linkage.
 */
int main()
{
	scpp::compiler_support::textbuilder text;
	text.append("Simple C++");
	text.append(" compiler bootstrap");
	return text.equals("Simple C++ compiler bootstrap") && !text.equals("wrong text") &&
			text.roundtrip_through_runtime()
		? 0
		: 1;
}
