#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"

#include <functional>

namespace scpp::regex {

[[nodiscard]] bool_t jit_available();
[[nodiscard]] string_t quote(const string_t &text);
[[nodiscard]] string_t quote(const string_t &text, const string_t &delimiter);

// Returns a packed vector [full, cap1, cap2, ...].
// Empty vector means no match. false means invalid pattern.
[[nodiscard]] result_or_false<vector_t<string_t>> match(const string_t &pattern, const string_t &subject);
[[nodiscard]] result_or_false<vector_t<string_t>> match(const string_t &pattern, const string_t &subject, const int_t &offset);
[[nodiscard]] result_or_false<hash_t<string_t, string_t>> match_named(const string_t &pattern, const string_t &subject);
[[nodiscard]] result_or_false<hash_t<string_t, string_t>> match_named(const string_t &pattern, const string_t &subject, const int_t &offset);

// Returns match-order rows [[full, cap1, ...], [full, cap1, ...], ...].
// Empty outer vector means no matches. false means invalid pattern.
[[nodiscard]] result_or_false<vector_t<vector_t<string_t>>> match_all(const string_t &pattern, const string_t &subject);
[[nodiscard]] result_or_false<vector_t<vector_t<string_t>>> match_all(const string_t &pattern, const string_t &subject, const int_t &offset);
[[nodiscard]] result_or_false<vector_t<vector_t<string_t>>> match_all_pattern_order(const string_t &pattern, const string_t &subject);
[[nodiscard]] result_or_false<vector_t<vector_t<string_t>>> match_all_pattern_order(const string_t &pattern, const string_t &subject, const int_t &offset);
[[nodiscard]] result_or_false<vector_t<hash_t<string_t, string_t>>> match_all_named(const string_t &pattern, const string_t &subject);
[[nodiscard]] result_or_false<vector_t<hash_t<string_t, string_t>>> match_all_named(const string_t &pattern, const string_t &subject, const int_t &offset);

// Filters packed input strings by pattern match.
[[nodiscard]] result_or_false<vector_t<string_t>> grep(const string_t &pattern, const vector_t<string_t> &input);

// Applies literal replacement and returns only rows where at least one replacement happened.
[[nodiscard]] result_or_false<vector_t<string_t>> filter(const string_t &pattern, const string_t &replacement, const vector_t<string_t> &input);
[[nodiscard]] result_or_false<vector_t<string_t>> filter(const string_t &pattern, const string_t &replacement, const vector_t<string_t> &input, const int_t &limit);
[[nodiscard]] result_or_false<vector_t<string_t>> filter(const string_t &pattern, const string_t &replacement, const vector_t<string_t> &input, int_t &count);
[[nodiscard]] result_or_false<vector_t<string_t>> filter(const string_t &pattern, const string_t &replacement, const vector_t<string_t> &input, const int_t &limit, int_t &count);

[[nodiscard]] result_or_false<string_t> replace_callback(const string_t &pattern, const std::function<string_t(vector_t<string_t>)> &callback, const string_t &subject);
[[nodiscard]] result_or_false<string_t> replace_callback(const string_t &pattern, const std::function<string_t(vector_t<string_t>)> &callback, const string_t &subject, const int_t &limit);
[[nodiscard]] result_or_false<string_t> replace_callback(const string_t &pattern, const std::function<string_t(vector_t<string_t>)> &callback, const string_t &subject, int_t &count);
[[nodiscard]] result_or_false<string_t> replace_callback(const string_t &pattern, const std::function<string_t(vector_t<string_t>)> &callback, const string_t &subject, const int_t &limit, int_t &count);
[[nodiscard]] result_or_false<string_t> replace_callback_array(const hash_t<std::function<string_t(vector_t<string_t>)>, string_t> &callbacks, const string_t &subject);
[[nodiscard]] result_or_false<string_t> replace_callback_array(const hash_t<std::function<string_t(vector_t<string_t>)>, string_t> &callbacks, const string_t &subject, const int_t &limit);
[[nodiscard]] result_or_false<string_t> replace_callback_array(const hash_t<std::function<string_t(vector_t<string_t>)>, string_t> &callbacks, const string_t &subject, int_t &count);
[[nodiscard]] result_or_false<string_t> replace_callback_array(const hash_t<std::function<string_t(vector_t<string_t>)>, string_t> &callbacks, const string_t &subject, const int_t &limit, int_t &count);

[[nodiscard]] result_or_false<string_t> replace(const string_t &pattern, const string_t &replacement, const string_t &subject);
[[nodiscard]] result_or_false<string_t> replace(const string_t &pattern, const string_t &replacement, const string_t &subject, const int_t &limit);
[[nodiscard]] result_or_false<string_t> replace(const string_t &pattern, const string_t &replacement, const string_t &subject, int_t &count);
[[nodiscard]] result_or_false<string_t> replace(const string_t &pattern, const string_t &replacement, const string_t &subject, const int_t &limit, int_t &count);

[[nodiscard]] result_or_false<vector_t<string_t>> split(const string_t &pattern, const string_t &subject);
[[nodiscard]] result_or_false<vector_t<string_t>> split(const string_t &pattern, const string_t &subject, const int_t &limit);
[[nodiscard]] result_or_false<vector_t<string_t>> split(const string_t &pattern, const string_t &subject, const int_t &limit, const int_t &flags);

} // namespace scpp::regex
