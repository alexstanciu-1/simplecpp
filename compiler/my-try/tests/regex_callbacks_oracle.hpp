#pragma once
#include "modules/regex/regex.hpp"
#include "core/string_support.hpp"
#include <iostream>
#include <limits>

// Shared by the direct provider and source-generated ABI proofs.
template<class Replace, class Table>
int regex_callbacks_oracle(Replace replace, Table table, bool baseline = false) {
	using S = scpp::string_t;
	using I = scpp::int_t<>;
	using V = scpp::vector_t<S>;
	using F = std::function<S(V)>;
	using H = scpp::hash_t<F, S>;
	int checks = 0, failures = 0, calls = 0;
	auto check = [&](bool ok, const char* name) {
		++checks;
		if (!ok) { ++failures; std::cout << "FAIL " << name << '\n'; }
	};
	auto text = [&](auto result, const std::string& expected, const char* name) {
		check(result.has_value().native_value() && result.value().native_value() == expected, name);
	};
	F literal = [&](V) { ++calls; return S{"$1\\1"}; };
	text(replace(S{"/a/"}, literal, S{"aba"}, I{-1}), "$1\\1b$1\\1", "literal output");
	check(calls == 2, "one invocation per match");
	calls = 0;
	text(replace(S{"/a/"}, literal, S{"aba"}, I{1}), "$1\\1ba", "positive limit");
	check(calls == 1, "limited calls");
	calls = 0;
	text(replace(S{"/a/"}, literal, S{"aba"}, I{0}), "aba", "zero limit");
	check(calls == 0, "zero callbacks");
	check(replace(S{"/broken"}, literal, S{"a"}, I{0}).is_false().native_value(), "zero validates pattern");
	check(calls == 0, "invalid pattern no callback");
	F x = [](V) { return S{"X"}; };
	text(replace(S{"/(?:)/"}, x, S{"ab"}, I{-1}), "XaXbX", "empty matches preserve bytes");
	text(replace(S{"/(?:)/"}, x, S{"ab"}, I{2}), "XaXb", "empty match limit");
	text(replace(S{"/(?:|a)/"}, x, S{"ab"}, I{-1}), "XXXbX", "nonempty alternative");
	text(replace(S{"/(?:)/u"}, x, S{"é"}, I{-1}), "XéX", "UTF search progression");
	text(replace(S{"/(?:)/"}, x, S{std::string("a\0b", 3)}, I{-1}),
		std::string("XaX\0XbX", 7), "binary subject");
	text(replace(S{"/z/"}, x, S{"ab"}, I{-1}), "ab", "no match");
	text(replace(S{"/(?:)/"}, x, S{""}, I{-1}), "X", "empty subject");
	F empty = [](V) { return S{""}; };
	text(replace(S{"/a/"}, empty, S{"aa"}, I{-1}), "", "empty successful payload");
	F captures = [&](V matches) {
		check(matches.size() == 3 && matches.at(I{0}).native_value() == "ab"
			&& matches.at(I{1}).native_value() == "a"
			&& matches.at(I{2}).native_value() == "b", "capture vector");
		matches.at(I{0}) = S{"local"};
		return S{"C"};
	};
	S original{"ab"};
	text(replace(S{"/(a)(b)/"}, captures, original, I{-1}), "C", "capture return");
	check(original.native_value() == "ab", "capture mutation leaves input");
	H callbacks;
	callbacks.set(S{"/a/"}, [](V) { return S{"b"}; });
	callbacks.set(S{"/b/"}, [](V) { return S{"C"}; });
	text(table(callbacks, S{"aa"}, I{-1}), "CC", "ordered current subject");
	text(table(callbacks, S{"aa"}, I{1}), "Ca", "limit per entry");
	text(table(callbacks, S{"aa"}, I{0}), "aa", "table zero");
	H none;
	text(table(none, S{"a"}, I{-1}), "a", "empty table");
	for (auto n : {std::int64_t{-2}, std::numeric_limits<std::int64_t>::min()}) {
		bool caught = false;
		try { static_cast<void>(replace(S{"/broken"}, literal, S{""}, I{n})); }
		catch (const scpp::ValueError&) { caught = true; }
		check(caught, "negative precedes pattern");
		caught = false;
		try { static_cast<void>(table(none, S{""}, I{n})); }
		catch (const scpp::ValueError&) { caught = true; }
		check(caught, "empty table validates limit");
	}
	calls = 0;
	H invalid;
	invalid.set(S{"/a/"}, literal);
	invalid.set(S{"/broken"}, literal);
	check(table(invalid, S{"a"}, I{0}).is_false().native_value(), "table zero validates patterns");
	check(calls == 0, "zero table callbacks");
	check(table(invalid, S{"a"}, I{-1}).is_false().native_value(), "later invalid pattern");
	check(calls == 1, "completed callback effects remain");
	calls = 0;
	F throwing = [&](V) -> S { ++calls; throw scpp::ValueError("callback failure"); };
	bool caught = false;
	try { static_cast<void>(replace(S{"/a/"}, throwing, original, I{-1})); }
	catch (const scpp::ValueError&) { caught = true; }
	check(caught && calls == 1 && original.native_value() == "ab", "exception preserves effects and source");
	F absent;
	caught = false;
	try { static_cast<void>(replace(S{"/a/"}, absent, original, I{-1})); }
	catch (const std::bad_function_call&) { caught = true; }
	check(caught, "absent callable invocation");
	text(replace(S{"/z/"}, absent, original, I{-1}), "ab", "absent not invoked without match");
	std::cout << "checks=" << checks << " failures=" << failures << '\n';
	return baseline ? (failures > 0 ? 0 : 2) : (failures == 0 ? 0 : 1);
}
