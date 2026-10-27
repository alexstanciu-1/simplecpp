#include "startup/model.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/io.hpp"

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
	 * Retain immutable generations while the driver edits files between read batches.
	 */
	static int test(const vector_string &arguments)
	{
		Compiler_Lifecycle::initialize(parse_configuration(arguments));
		const auto &sources = Model::sources();
		const auto a = sources.find(0, string("a.phs"));
		const auto b = sources.find(0, string("b.phs"));
		const auto original = sources.at(a).buffer;
		check(original.equals(string("old")), "Initial source not loaded");
		check(sources.at(b).buffer.present() && sources.at(b).buffer.byte_size() == 0, "Empty source lost");
		check(sources.at(a).status == source_status::added, "Read acknowledged change");
		boolean bounds_failed = false;
		try {
			(void)original.byte_at(3);
		}
		catch (...) {
			bounds_failed = true;
		}
		check(bounds_failed, "Unchecked byte access");

		while (true)
		{
			print_line(string("ready"));
			fflush(nullptr);
			const int command = getchar();
			if (command == 'q') {
				break;
			}
			check(command != -1, "Driver closed unexpectedly");
			if (command == 's') {
				Compiler_Lifecycle::rescan_sources();
				continue;
			}
			if (command == 'n') {
				Compiler_Lifecycle::notify_source(a);
			}
			if (command == 'f')
			{
				const auto previous = sources.at(a).buffer;
				const auto other = sources.at(b).buffer;
				const auto revision = sources.at(a).loaded_revision;
				boolean failed = false;
				try {
					Compiler_Lifecycle::load_sources(true);
				}
				catch (...) {
					failed = true;
				}
				check(failed && sources.at(a).buffer.same_storage(previous) &&
					sources.at(b).buffer.same_storage(other) && sources.at(a).loaded_revision == revision,
					"Failed batch published partial state");
				failed = false;
				try {
					Compiler_Lifecycle::acknowledge_source(a);
				}
				catch (...) {
					failed = true;
				}
				check(failed, "Acknowledged unread revision");
				continue;
			}
			const auto previous = sources.at(a).buffer;
			Compiler_Lifecycle::load_sources(true);
			if (command == 'u') {
				check(sources.reads().files_read == 0 && sources.at(a).buffer.same_storage(previous),
					"Loaded pending source read twice");
			}
			if (command == 'n') {
				check(sources.reads().workers == 1 && sources.reads().work_ns > 0, "Missing profiled batch");
				check(sources.reads().files_read == 1 && !sources.at(a).buffer.same_storage(previous),
					"Notification failed to invalidate loaded revision");
			}
			if (command == 'r') {
				check(sources.at(a).buffer.equals(string("replacement")), "Replacement missing");
				check(sources.at(b).buffer.byte_size() == 3 && sources.at(b).buffer.byte_at(1) == 0,
					"Embedded NUL lost");
			}
			if (command == 'd') {
				check(sources.at(a).status == source_status::deleted && sources.reads().files_read == 0,
					"Deleted source read");
			}
			check(original.equals(string("old")), "Retained source generation invalidated");
		}
		Compiler_Lifecycle::reset();
		check(original.equals(string("old")), "Reset invalidated retained generation");
		return 0;
	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::test);
}
