#include "startup/model.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/io.hpp"

// Test-only handshake with the fixture driver; filesystem edits happen between scans.
extern "C" int getchar();
extern "C" int fflush(void *);

namespace scpp::compiler
{
	static void check(boolean condition, const char *message)
	{
		if (!condition) {
			fail(string(message));
		}
	}

	/**
	 * Keep one model alive while the Python driver edits its source tree.
	 */
	static int test(const vector_string &arguments)
	{
		auto config = parse_configuration(arguments);
		Compiler_Lifecycle::initialize(config);
		const auto &files = Model::sources();
		const auto a = files.find(0, string("a.phs"));
		const auto b = files.find(0, string("sub/b.phs"));
		check(files.size() == 2 && a == 0 && b == 1, "Wrong discovery membership/order");
		const file_record *stable = &files.at(a);
		while (true)
		{
			print_line(string("ready"));
			fflush(nullptr);
			const int command = getchar();
			if (command == 'q') {
				break;
			}
			if (command == -1) {
				fail(string("Fixture handshake ended early"));
			}
			if (command == 'u')
			{
				Compiler_Lifecycle::load_sources();
				for (int64 i = 0; i < files.size(); ++i) {
					Compiler_Lifecycle::acknowledge_source(i);
				}
			}
			if (command == 'n') {
				Compiler_Lifecycle::notify_source(a);
			}
			if (command == 'f')
			{
				const auto count = files.size();
				const auto old_bytes = files.at(a).bytes;
				const auto old_status = files.status(a);
				boolean failed = false;
				try {
					Compiler_Lifecycle::rescan_sources();
				}
				catch (...) {
					failed = true;
				}
				check(failed && files.size() == count && files.at(a).bytes == old_bytes &&
					files.status(a) == old_status, "Failed scan modified live inventory");
			}
			else {
				Compiler_Lifecycle::rescan_sources();
			}
			check(&files.at(a) == stable, "Rescan replaced stable record");
			if (command == 'u' || command == 'e') {
				check(files.status(a) == source_status::unchanged, "Unchanged scan changed file");
			}
			if (command == 'c' || command == 'n' || command == 'p') {
				check(files.status(a) == source_status::changed, "Pending change was lost");
			}
			if (command == 'a') {
				check(files.size() == 3 && files.find(0, string("new.phs")) == 2 &&
					files.status(2) == source_status::added, "Addition changed existing identities");
			}
			if (command == 'd') {
				check(files.status(b) == source_status::deleted, "Deletion lost tombstone");
			}
			if (command == 'r') {
				check(files.find(0, string("sub/b.phs")) == b && files.status(b) == source_status::added,
					"Reappearance lost identity");
			}
		}
		Compiler_Lifecycle::reset();
		return 0;
	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::test);
}
