#pragma once
#include "scpp/lang/php.hpp"
#include <iostream>
#include <limits>
#include <string>
#include <vector>

// Independent expected bytes/states, shared by direct-provider and source-call proofs.
template<class Replace, class Filter>
int regex_replacement_oracle(Replace replace, Filter filter, bool baseline = false) {
	using S = scpp::string_t;
	using I = scpp::int_t<>;
	using V = scpp::vector_t<S>;
	int checks = 0;
	int failures = 0;
	auto check = [&](bool ok, const char* label) {
		++checks;
		if (!ok) { ++failures; std::cout << "FAIL " << label << '\n'; }
	};
	struct Row { std::string pattern, replacement, subject, expected; std::int64_t limit; };
	const std::vector<Row> rows = {
		{"/a/", "X", "aba", "XbX", -1}, {"/a/", "X", "aba", "Xba", 1},
		{"/a/", "X", "aba", "aba", 0}, {"/a/", "X", "aba", "XbX", 10},
		{"/(a)(b)/", "$2${1}\\1", "ab ab", "baa baa", -1},
		{"/(a)?b/", "[$1]", "b ab", "[] [a]", -1},
		{"/z/", "X", "aba", "aba", -1}, {"/a/", "a", "a", "a", -1},
		{"/a/", "", "a", "", -1}, {"/a/", "X", "", "", -1},
		{"/(?:)/", "-", "ab", "-a-b-", -1}, {"/(?:)/", "-", "ab", "-ab", 1},
		{"/(?:)/", "-", "", "-", -1}, {"/(?:|a)/", "X", "a", "XXX", -1},
		{"/(?:)/u", "-", "é", "-é-", -1},
		{"/b/", std::string{"X\0Y",3}, std::string{"a\0b",3}, std::string{"a\0X\0Y",5}, -1},
	};
	for (const auto& row : rows) {
		auto result = replace(S{row.pattern}, S{row.replacement}, S{row.subject}, I{row.limit});
		check(result.has_value().native_value() && result.value().native_value() == row.expected, "replacement bytes");
	}
	V input; input.append(S{"aba"}); input.append(S{"none"}); input.append(S{"aa"});
	for (std::int64_t limit : {-1, 0, 1, 2}) {
		auto result = filter(S{"/a/"}, S{"X"}, input, I{limit});
		check(result.has_value().native_value(), "filter payload");
		if (result.has_value().native_value()) {
			const auto& out = result.value();
			check(limit == 0 ? out.size() == 0 : out.size() == 2
				&& out.at(I{0}).native_value() == (limit == 1 ? "Xba" : "XbX")
				&& out.at(I{1}).native_value() == (limit == 1 ? "Xa" : "XX"), "per-row limit and order");
		}
	}
	V same; same.append(S{"a"});
	auto identical = filter(S{"/a/"}, S{"a"}, same, I{-1});
	check(identical.has_value().native_value() && identical.value().size()==1, "identical bytes retained");
	auto empty_text = filter(S{"/a/"}, S{""}, same, I{-1});
	check(empty_text.has_value().native_value() && empty_text.value().size()==1 && empty_text.value().at(I{0}).native_value().empty(), "empty payload row retained");
	V captures; captures.append(S{"ab"}); captures.append(S{"z"});
	auto expanded = filter(S{"/(a)(b)/"}, S{"$2${1}\\1"}, captures, I{-1});
	check(expanded.has_value().native_value() && expanded.value().size()==1 && expanded.value().at(I{0}).native_value()=="baa", "filter backreferences");
	V zero; zero.append(S{"ab"});
	auto zero_result = filter(S{"/(?:)/"}, S{"-"}, zero, I{-1});
	check(zero_result.has_value().native_value() && zero_result.value().at(I{0}).native_value()=="-a-b-", "filter zero-length bytes");
	V binary; binary.append(S{std::string{"a\0b",3}});
	auto binary_result = filter(S{"/b/"}, S{std::string{"X\0Y",3}}, binary, I{1});
	check(binary_result.has_value().native_value() && binary_result.value().at(I{0}).native_value()==std::string{"a\0X\0Y",5}, "filter binary bytes");
	V empty;
	for (std::int64_t limit : {-1, 0, 1}) {
		check(filter(S{"/a/"}, S{"X"}, empty, I{limit}).has_value().native_value(), "empty valid input");
		check(filter(S{"/broken"}, S{"X"}, empty, I{limit}).is_false().native_value(), "empty invalid pattern");
		check(filter(S{"/broken"}, S{"X"}, input, I{limit}).is_false().native_value(), "invalid filter pattern");
		check(replace(S{"/broken"}, S{"X"}, S{"a"}, I{limit}).is_false().native_value(), "invalid replacement pattern");
	}
	for (auto limit : {std::int64_t{-2}, std::numeric_limits<std::int64_t>::min()}) {
		for (const char* pattern : {"/a/", "/broken"}) {
			bool caught = false;
			try { replace(S{pattern}, S{"X"}, S{"a"}, I{limit}); }
			catch (const scpp::ValueError&) { caught = true; }
			check(caught, "replacement negative precedence");
			for (const V* source : {&empty, &input}) {
				caught = false;
				try { filter(S{pattern}, S{"X"}, *source, I{limit}); }
				catch (const scpp::ValueError&) { caught = true; }
				check(caught, "filter negative precedence");
			}
		}
	}
	check(input.size()==3 && input.at(I{0}).native_value()=="aba", "source preserved");
	std::cout << "checks=" << checks << ";failures=" << failures << '\n';
	return baseline ? 0 : (failures == 0 ? 0 : 1);
}
