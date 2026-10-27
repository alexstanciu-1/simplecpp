#include "tokenize/command.hpp"
#include "tokenize/scanner.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/io.hpp"
#include "scpp_compiler/textbuilder.hpp"

namespace scpp::compiler
{
	/**
	 * Check one ephemeral source using the shared scanner; own its generation until exit.
	 * Catalog discovery, filesystem inventory and native execution are outside this command.
	 */
	int tokenize_command(const vector_string &arguments)
	{
		const auto from_stdin = arguments.read(0).equals("-");
		if (arguments.size() != (from_stdin ? 1 : 2)) {
			fail(string("Expected '-' alone or '-e SOURCE'; execution arguments are not supported yet"));
		}
		const string label(from_stdin ? "<stdin>" : "<command-line>");
		try
		{
			const auto text = from_stdin ? read_standard_input() : arguments.read(1);
			const auto source = token_buffer::from_text(text);
			const auto tokens = scan_tokens(source);
			textbuilder report;
			report.append("{\"state\":\"tokenized\",\"execution_available\":false,\"source\":\"");
			report.append(label);
			report.append("\",\"bytes\":");
			report.append_number(source.byte_size());
			report.append(",\"tokens\":");
			report.append_number(tokens.size());
			report.append("}");
			print_line(report.finish());
		}
		catch (...) {
			rethrow_with_context(label);
		}
		return 0;
	}
}
