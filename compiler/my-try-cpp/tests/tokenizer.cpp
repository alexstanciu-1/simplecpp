#include "tokenize/scanner.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/io.hpp"
#include "scpp_compiler/token_jobs.hpp"

namespace scpp::compiler
{
	using compiler_support::vector_string;
	static void check(boolean condition, const char *message)
	{
		if (!condition) {
			fail(string(message));
		}
	}

	static token_sequence scan(const char *text)
	{
		return scan_tokens(token_buffer::from_text(string(text)));
	}

	/**
	 * Escaped source snapshots retain ownership for both temporary and replaced sequences.
	 */
	static void check_source_ownership()
	{
		const auto &temporary_source = scan("echo 1;").source();
		check(temporary_source.equals(string("echo 1;")), "Temporary sequence invalidated source");
		auto generation = scan("return 2;");
		const auto &retained = generation.source();
		check(retained.same_storage(generation.source()), "Source snapshot copied bytes");
		generation = token_sequence();
		check(retained.equals(string("return 2;")), "Replacement invalidated source snapshot");
	}

	/**
	 * Reject missing callbacks in empty and populated batches; rejection leaves work reusable.
	 */
	static void check_scanner_contract()
	{
		token_jobs batch;
		for (int64 populated = 0; populated < 2; ++populated)
		{
			boolean failed = false;
			try {
				(void)batch.execute(2, nullptr);
			}
			catch (...) {
				failed = true;
			}
			check(failed, "Null scanner accepted");
			if (populated == 0) {
				batch.add(0, string("contract"), token_buffer::from_text(string("return 1;")), 0, 1);
			}
		}
		const auto metrics = batch.execute(2, scan_tokens);
		check(metrics.files_tokenized == 1 && batch.tokens(0).size() == 3,
			"Rejected callback corrupted pending work");
	}

	/**
	 * Independent expected tags, byte boundaries, EOF handling and immutable ownership.
	 */
	static int test(const vector_string &)
	{
		check_source_ownership();
		check_scanner_contract();
		const auto tokens = scan(" /*x*/$x->field-1e-3; #[Override] vector<vector<int>> 'a' \"b\"");
		check(tokens.size() == 18, "Wrong token count");
		check(tokens.at(0).kind() == token_kind::variable && tokens.at(0).offset() == 6 &&
			tokens.at(0).length() == 2, "Variable span");
		check(tokens.at(1).kind() == token_kind::member, "Member operator");
		check(tokens.at(4).kind() == token_kind::floating && tokens.at(4).length() == 4, "Exponent");
		check(tokens.at(6).kind() == token_kind::attribute, "Attribute/comment distinction");
		check(tokens.at(14).kind() == token_kind::greater && tokens.at(15).kind() == token_kind::greater,
			"Nested angle tokens merged");
		check(tokens.at(16).kind() == token_kind::single_string &&
			tokens.at(17).kind() == token_kind::double_string, "Quote kinds");
		check(scan("function return true false null clean void").at(0).kind() == token_kind::kw_function,
			"Keyword tag");
		check(scan("Function").at(0).kind() == token_kind::identifier, "Keyword case changed");
		check(scan(" \r\n# x\n//y\n/*z*/").size() == 0 && scan("").present(), "Empty generation");
		const auto source = token_buffer::from_text(string("abc"));
		check(source.peek(3) == -1 && source.peek(18446744073709551615ULL) == -1, "Lookahead EOF");
		check(source.matches(0, 3, "abc") && !source.matches(2, 3, "abc"), "Spelling bounds");
		const auto copy = tokens;
		check(copy.same_storage(tokens) && copy.source().same_storage(tokens.source()), "Generation copied bytes");
		boolean failed = false;
		try {
			(void)tokens.at(tokens.size());
		}
		catch (...) {
			failed = true;
		}
		check(failed, "Unchecked token index");
		for (int64 index = 0; index < 4; ++index)
		{
			failed = false;
			try {
				(void)token(index == 0 ? uint64(4294967296ULL) : uint64(0),
					index == 1 ? uint64(16777216) : (index == 2 ? uint64(0) : uint64(1)),
					index == 3 ? token_kind::reserved : token_kind::identifier);
			}
			catch (...) {
				failed = true;
			}
			check(failed, "Compact token accepted invalid bounds/tag");
		}
		const token maximum(4294967295ULL, 16777215, token_kind::kw_xor);
		check(maximum.offset() == 4294967295u && maximum.length() == 16777215 &&
			maximum.kind() == token_kind::kw_xor, "Packed fields corrupt each other");
		return 0;
	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::test);
}
