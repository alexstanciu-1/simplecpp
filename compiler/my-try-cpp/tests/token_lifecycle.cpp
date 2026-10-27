#include "sources/inventory.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/io.hpp"

extern "C" int getchar();
extern "C" int fflush(void *);

namespace scpp::compiler
{
	static void check(boolean value, const char *message)
	{
		if (!value) {
			fail(string(message));
		}
	}

	/**
	 * Retain token generations while the driver edits, deletes and repairs source files.
	 */
	static int test(const vector_string &arguments)
	{
		vector_string roots(arguments);
		validate_source_roots(roots);
		source_inventory sources;
		sources.scan(roots, 8);
		sources.load(roots, 8);
		sources.tokenize(8);
		const auto original = sources.at(0).tokens;
		check(original.source().equals(string("return 1;")), "Initial token source");
		while (true)
		{
			print_line(string("ready"));
			fflush(nullptr);
			const int command = getchar();
			check(command != -1, "Unexpected fixture EOF");
			if (command == 'q') {
				break;
			}
			const auto a = sources.at(0).tokens;
			const auto b = sources.at(1).tokens;
			const auto revision = sources.at(0).tokenized_revision;
			if (command != 'u') {
				sources.scan(roots, 8);
				sources.load(roots, 8);
			}
			if (command == 'n')
			{
				sources.notify(0);
				boolean rejected = false;
				try {
					sources.tokenize(8);
				}
				catch (...) {
					rejected = true;
				}
				check(rejected, "Tokenized unread notified revision");
				sources.load(roots, 8);
			}
			boolean failed = false;
			try {
				sources.tokenize(8, true);
			}
			catch (...) {
				failed = true;
			}
			if (command == 'f') {
				check(failed && sources.at(0).tokenized_revision == revision && sources.at(0).tokens.same_storage(a) && sources.at(1).tokens.same_storage(b),
					"Lexical failure published partial generation");
			}
			else {
				check(!failed, "Unexpected lexical failure");
			}
			if (command == 'u') {
				check(sources.token_metrics().files_tokenized == 0 && sources.at(0).tokens.same_storage(a),
					"Pending loaded tokens scanned again");
			}
			if (command == 'n') {
				check(sources.token_metrics().files_tokenized == 1 && !sources.at(0).tokens.same_storage(a),
					"Notification did not invalidate tokens");
			}
			check(original.source().equals(string("return 1;")) && original.size() == 3,
				"Old generation invalidated");
		}
		{
			source_inventory empty;
			sources.swap(empty);
		}
		check(original.source().equals(string("return 1;")), "Reset invalidated old tokens");
		return 0;
	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::test);
}
