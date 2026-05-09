#include "modules/regex/regex.hpp"

#include "core/string_support.hpp"

#define PCRE2_CODE_UNIT_WIDTH 8
#include <pcre2.h>

#include <cstdint>
#include <memory>
#include <string>
#include <utility>

namespace scpp::regex {
namespace {

using compiled_code_ptr = std::unique_ptr<pcre2_code, decltype(&pcre2_code_free)>;
using match_data_ptr = std::unique_ptr<pcre2_match_data, decltype(&pcre2_match_data_free)>;

[[nodiscard]] bool is_regex_meta_char(const char ch) noexcept {
	switch (ch) {
		case '.':
		case '\\':
		case '+':
		case '*':
		case '?':
		case '[':
		case '^':
		case ']':
		case '$':
		case '(':
		case ')':
		case '{':
		case '}':
		case '=':
		case '!':
		case '<':
		case '>':
		case '|':
		case ':':
		case '-':
		case '#':
			return true;
		default:
			return false;
	}
}

struct parsed_pattern final {
	std::string pattern;
	uint32_t options = 0;
};

struct replace_result final {
	string_t text;
	std::size_t replacements = 0;
};

[[nodiscard]] bool probe_jit_available_impl() {
	uint32_t jit_compiled = 0;
	const int config_rc = pcre2_config(PCRE2_CONFIG_JIT, &jit_compiled);
	if (config_rc != 0 || jit_compiled == 0) {
		return false;
	}

	int error_code = 0;
	PCRE2_SIZE error_offset = 0;
	pcre2_code *raw = pcre2_compile(
		reinterpret_cast<PCRE2_SPTR>("a"),
		PCRE2_ZERO_TERMINATED,
		0,
		&error_code,
		&error_offset,
		nullptr);
	if (raw == nullptr) {
		return false;
	}

	compiled_code_ptr compiled(raw, &pcre2_code_free);
	const int jit_rc = pcre2_jit_compile(compiled.get(), PCRE2_JIT_COMPLETE);
	return jit_rc == 0;
}

void maybe_enable_jit(pcre2_code *compiled) {
	static const bool jit_available = probe_jit_available_impl();
	if (!jit_available || compiled == nullptr) {
		return;
	}

	// JIT is opportunistic in this pass. Unsupported patterns or runtime
	// allocation failures must fall back to the interpreter without surfacing
	// as user-visible regex errors.
	static_cast<void>(pcre2_jit_compile(compiled, PCRE2_JIT_COMPLETE));
}

[[nodiscard]] char closing_delimiter_for(const char opening) noexcept {
	switch (opening) {
		case '(': return ')';
		case '[': return ']';
		case '{': return '}';
		case '<': return '>';
		default: return opening;
	}
}

[[nodiscard]] result_or_false<parsed_pattern> parse_pattern(const string_t &pattern) {
	const std::string &native = pattern.native_value();
	if (native.size() < 3u) {
		return false_sentinel;
	}

	const char opening = native[0];
	const char closing = closing_delimiter_for(opening);
	if ((opening >= 'A' && opening <= 'Z')
		|| (opening >= 'a' && opening <= 'z')
		|| (opening >= '0' && opening <= '9')
		|| opening == '\\'
		|| opening == ' ') {
		return false_sentinel;
	}

	bool escaped = false;
	int nesting = 0;
	std::size_t closing_index = std::string::npos;
	for (std::size_t index = 1; index < native.size(); ++index) {
		const char ch = native[index];
		if (escaped) {
			escaped = false;
			continue;
		}
		if (ch == '\\') {
			escaped = true;
			continue;
		}
		if (opening != closing && ch == opening) {
			++nesting;
			continue;
		}
		if (ch == closing) {
			if (opening != closing && nesting > 0) {
				--nesting;
				continue;
			}
			closing_index = index;
			break;
		}
	}

	if (closing_index == std::string::npos || closing_index <= 1u) {
		return false_sentinel;
	}

	uint32_t options = 0;
	for (std::size_t index = closing_index + 1u; index < native.size(); ++index) {
		switch (native[index]) {
			case 'i': options |= PCRE2_CASELESS; break;
			case 'm': options |= PCRE2_MULTILINE; break;
			case 's': options |= PCRE2_DOTALL; break;
			case 'u': options |= PCRE2_UTF | PCRE2_UCP; break;
			case 'x': options |= PCRE2_EXTENDED; break;
			case 'A': options |= PCRE2_ANCHORED; break;
			case 'D': options |= PCRE2_DOLLAR_ENDONLY; break;
			case 'U': options |= PCRE2_UNGREEDY; break;
			default: return false_sentinel;
		}
	}

	return parsed_pattern{
		.pattern = native.substr(1u, closing_index - 1u),
		.options = options,
	};
}

[[nodiscard]] result_or_false<compiled_code_ptr> compile_pattern(const string_t &pattern) {
	const auto parsed = parse_pattern(pattern);
	if (!parsed.has_value().native_value()) {
		return false_sentinel;
	}

	int error_code = 0;
	PCRE2_SIZE error_offset = 0;
	pcre2_code *compiled = pcre2_compile(
		reinterpret_cast<PCRE2_SPTR>(parsed.value().pattern.c_str()),
		PCRE2_ZERO_TERMINATED,
		parsed.value().options,
		&error_code,
		&error_offset,
		nullptr);
	if (compiled == nullptr) {
		return false_sentinel;
	}

	maybe_enable_jit(compiled);
	return compiled_code_ptr(compiled, &pcre2_code_free);
}

[[nodiscard]] std::string quote_impl(const std::string &text, const char delimiter, const bool escape_delimiter) {
	std::string out;
	out.reserve(text.size() * 2u);
	for (const char ch : text) {
		if (is_regex_meta_char(ch) || (escape_delimiter && ch == delimiter)) {
			out.push_back('\\');
		}
		out.push_back(ch);
	}
	return out;
}

[[nodiscard]] vector_t<string_t> build_match_vector(
	const string_t &subject,
	pcre2_match_data *match_data,
	const int capture_count
) {
	vector_t<string_t> out;
	PCRE2_SIZE *ovector = pcre2_get_ovector_pointer(match_data);
	for (int index = 0; index < capture_count; ++index) {
		const PCRE2_SIZE start = ovector[index * 2];
		const PCRE2_SIZE end = ovector[index * 2 + 1];
		if (start == PCRE2_UNSET || end == PCRE2_UNSET) {
			out.append(string_t(""));
			continue;
		}
		out.append(string_t(subject.native_value().substr(start, end - start)));
	}
	return out;
}

[[nodiscard]] vector_t<vector_t<string_t>> transpose_match_rows(const vector_t<vector_t<string_t>> &rows) {
	vector_t<vector_t<string_t>> out;
	if (rows.empty().native_value()) {
		return out;
	}

	const std::size_t capture_count = rows[0].size();
	for (std::size_t capture_index = 0; capture_index < capture_count; ++capture_index) {
		vector_t<string_t> capture_values;
		for (std::size_t row_index = 0; row_index < rows.size(); ++row_index) {
			capture_values.append(rows[row_index][capture_index]);
		}
		out.append(std::move(capture_values));
	}
	return out;
}

[[nodiscard]] bool append_capture_by_index(
	std::string &out,
	const string_t &subject,
	pcre2_match_data *match_data,
	const int capture_count,
	const int capture_index
) {
	if (capture_index < 0 || capture_index >= capture_count) {
		return false;
	}

	PCRE2_SIZE *ovector = pcre2_get_ovector_pointer(match_data);
	const PCRE2_SIZE start = ovector[capture_index * 2];
	const PCRE2_SIZE end = ovector[capture_index * 2 + 1];
	if (start == PCRE2_UNSET || end == PCRE2_UNSET) {
		return true;
	}

	out.append(subject.native_value().substr(start, end - start));
	return true;
}

[[nodiscard]] hash_t<string_t, string_t> build_named_match_table(
	const string_t &subject,
	const pcre2_code *compiled,
	pcre2_match_data *match_data,
	const int capture_count
) {
	hash_t<string_t, string_t> out;
	const auto packed = build_match_vector(subject, match_data, capture_count);
	for (std::size_t index = 0; index < packed.size(); ++index) {
		out.set(string_t(std::to_string(index)), packed[index]);
	}

	uint32_t name_count = 0;
	uint32_t entry_size = 0;
	PCRE2_SPTR name_table = nullptr;
	static_cast<void>(pcre2_pattern_info(compiled, PCRE2_INFO_NAMECOUNT, &name_count));
	static_cast<void>(pcre2_pattern_info(compiled, PCRE2_INFO_NAMEENTRYSIZE, &entry_size));
	static_cast<void>(pcre2_pattern_info(compiled, PCRE2_INFO_NAMETABLE, &name_table));
	for (uint32_t name_index = 0; name_index < name_count; ++name_index) {
		const PCRE2_SPTR entry = name_table + (name_index * entry_size);
		const int capture_index = (static_cast<int>(entry[0]) << 8) | static_cast<int>(entry[1]);
		const char *capture_name = reinterpret_cast<const char *>(entry + 2);
		std::string named_value;
		static_cast<void>(append_capture_by_index(named_value, subject, match_data, capture_count, capture_index));
		out.set(string_t(capture_name), string_t(std::move(named_value)));
	}

	return out;
}

[[nodiscard]] std::string expand_replacement(
	const string_t &replacement,
	const string_t &subject,
	pcre2_match_data *match_data,
	const int capture_count
) {
	const std::string &native = replacement.native_value();
	std::string out;
	out.reserve(native.size());

	for (std::size_t index = 0; index < native.size(); ++index) {
		const char ch = native[index];

		if (ch == '$') {
			if (index + 1u < native.size() && native[index + 1u] == '{') {
				std::size_t cursor = index + 2u;
				int capture_index = 0;
				bool has_digits = false;
				while (cursor < native.size() && native[cursor] >= '0' && native[cursor] <= '9') {
					has_digits = true;
					capture_index = (capture_index * 10) + static_cast<int>(native[cursor] - '0');
					++cursor;
				}
				if (has_digits && cursor < native.size() && native[cursor] == '}') {
					static_cast<void>(append_capture_by_index(out, subject, match_data, capture_count, capture_index));
					index = cursor;
					continue;
				}
			}

			if (index + 1u < native.size() && native[index + 1u] >= '0' && native[index + 1u] <= '9') {
				std::size_t cursor = index + 1u;
				int capture_index = 0;
				while (cursor < native.size() && native[cursor] >= '0' && native[cursor] <= '9') {
					capture_index = (capture_index * 10) + static_cast<int>(native[cursor] - '0');
					++cursor;
				}
				static_cast<void>(append_capture_by_index(out, subject, match_data, capture_count, capture_index));
				index = cursor - 1u;
				continue;
			}
		}

		if (ch == '\\' && index + 1u < native.size() && native[index + 1u] >= '0' && native[index + 1u] <= '9') {
			std::size_t cursor = index + 1u;
			int capture_index = 0;
			while (cursor < native.size() && native[cursor] >= '0' && native[cursor] <= '9') {
				capture_index = (capture_index * 10) + static_cast<int>(native[cursor] - '0');
				++cursor;
			}
			static_cast<void>(append_capture_by_index(out, subject, match_data, capture_count, capture_index));
			index = cursor - 1u;
			continue;
		}

		out.push_back(ch);
	}

	return out;
}

[[nodiscard]] PCRE2_SIZE next_match_start(const PCRE2_SIZE match_start, const PCRE2_SIZE match_end, const std::size_t subject_size) {
	if (match_end != match_start) {
		return match_end;
	}
	if (match_end >= subject_size) {
		return subject_size + 1u;
	}
	return match_end + 1u;
}

[[nodiscard]] PCRE2_SIZE normalize_match_offset(const int_t &offset, const std::size_t subject_size) {
	const auto native_offset = offset.native_value();
	if (native_offset >= 0) {
		if (static_cast<std::uint64_t>(native_offset) > subject_size) {
			return static_cast<PCRE2_SIZE>(subject_size + 1u);
		}
		return static_cast<PCRE2_SIZE>(native_offset);
	}

	const auto from_end = static_cast<std::int64_t>(subject_size) + native_offset;
	if (from_end <= 0) {
		return 0;
	}
	return static_cast<PCRE2_SIZE>(from_end);
}

[[nodiscard]] result_or_false<replace_result> replace_impl(
	const string_t &pattern,
	const string_t &replacement,
	const string_t &subject,
	const int_t &limit
) {
	const auto native_limit = limit.native_value();
	if (native_limit < 0 && native_limit != -1) {
		throw scpp::ValueError("preg_replace(): negative limit other than -1 is not supported in the first pass");
	}
	if (native_limit == 0) {
		return replace_result{subject, 0u};
	}

	auto compiled = compile_pattern(pattern);
	if (!compiled.has_value().native_value()) {
		return false_sentinel;
	}

	match_data_ptr match_data(
		pcre2_match_data_create_from_pattern(compiled.value().get(), nullptr),
		&pcre2_match_data_free);
	if (!match_data) {
		return false_sentinel;
	}

	const std::string &text = subject.native_value();
	std::string out;
	out.reserve(text.size());
	PCRE2_SIZE start_offset = 0;
	std::size_t replacements = 0;
	const bool unlimited = (native_limit < 0);
	const std::size_t max_replacements = unlimited ? 0u : static_cast<std::size_t>(native_limit);

	while (start_offset <= text.size()) {
		if (!unlimited && replacements >= max_replacements) {
			break;
		}

		const int rc = pcre2_match(
			compiled.value().get(),
			reinterpret_cast<PCRE2_SPTR>(text.c_str()),
			text.size(),
			start_offset,
			0,
			match_data.get(),
			nullptr);
		if (rc == PCRE2_ERROR_NOMATCH) {
			break;
		}
		if (rc < 0) {
			return false_sentinel;
		}

		PCRE2_SIZE *ovector = pcre2_get_ovector_pointer(match_data.get());
		const PCRE2_SIZE match_start = ovector[0];
		const PCRE2_SIZE match_end = ovector[1];

		out.append(text.substr(start_offset, match_start - start_offset));
		out.append(expand_replacement(replacement, subject, match_data.get(), rc));
		++replacements;

		start_offset = next_match_start(match_start, match_end, text.size());
		if (start_offset > text.size()) {
			start_offset = text.size();
			break;
		}
	}

	out.append(text.substr(start_offset));
	return replace_result{
		.text = string_t(std::move(out)),
		.replacements = replacements,
	};
}

} // namespace

bool_t jit_available() {
	static const bool available = probe_jit_available_impl();
	return bool_t(available);
}

string_t quote(const string_t &text) {
	return string_t(quote_impl(text.native_value(), '\0', false));
}

string_t quote(const string_t &text, const string_t &delimiter) {
	if (delimiter.native_value().empty()) {
		return quote(text);
	}
	return string_t(quote_impl(text.native_value(), delimiter.native_value()[0], true));
}

result_or_false<vector_t<string_t>> match(const string_t &pattern, const string_t &subject) {
	return match(pattern, subject, int_t(0));
}

result_or_false<vector_t<string_t>> match(const string_t &pattern, const string_t &subject, const int_t &offset) {
	auto compiled = compile_pattern(pattern);
	if (!compiled.has_value().native_value()) {
		return false_sentinel;
	}

	const std::string &text = subject.native_value();
	const PCRE2_SIZE start_offset = normalize_match_offset(offset, text.size());
	if (start_offset > text.size()) {
		return vector_t<string_t>();
	}

	match_data_ptr match_data(
		pcre2_match_data_create_from_pattern(compiled.value().get(), nullptr),
		&pcre2_match_data_free);
	if (!match_data) {
		return false_sentinel;
	}

	const int rc = pcre2_match(
		compiled.value().get(),
		reinterpret_cast<PCRE2_SPTR>(text.c_str()),
		text.size(),
		start_offset,
		0,
		match_data.get(),
		nullptr);
	if (rc == PCRE2_ERROR_NOMATCH) {
		return vector_t<string_t>();
	}
	if (rc < 0) {
		return false_sentinel;
	}

	return build_match_vector(subject, match_data.get(), rc);
}

result_or_false<hash_t<string_t, string_t>> match_named(const string_t &pattern, const string_t &subject) {
	return match_named(pattern, subject, int_t(0));
}

result_or_false<hash_t<string_t, string_t>> match_named(const string_t &pattern, const string_t &subject, const int_t &offset) {
	auto compiled = compile_pattern(pattern);
	if (!compiled.has_value().native_value()) {
		return false_sentinel;
	}

	const std::string &text = subject.native_value();
	const PCRE2_SIZE start_offset = normalize_match_offset(offset, text.size());
	if (start_offset > text.size()) {
		return hash_t<string_t, string_t>();
	}

	match_data_ptr match_data(
		pcre2_match_data_create_from_pattern(compiled.value().get(), nullptr),
		&pcre2_match_data_free);
	if (!match_data) {
		return false_sentinel;
	}

	const int rc = pcre2_match(
		compiled.value().get(),
		reinterpret_cast<PCRE2_SPTR>(text.c_str()),
		text.size(),
		start_offset,
		0,
		match_data.get(),
		nullptr);
	if (rc == PCRE2_ERROR_NOMATCH) {
		return hash_t<string_t, string_t>();
	}
	if (rc < 0) {
		return false_sentinel;
	}

	return build_named_match_table(subject, compiled.value().get(), match_data.get(), rc);
}

result_or_false<vector_t<vector_t<string_t>>> match_all(const string_t &pattern, const string_t &subject) {
	return match_all(pattern, subject, int_t(0));
}

result_or_false<vector_t<vector_t<string_t>>> match_all(const string_t &pattern, const string_t &subject, const int_t &offset) {
	auto compiled = compile_pattern(pattern);
	if (!compiled.has_value().native_value()) {
		return false_sentinel;
	}

	match_data_ptr match_data(
		pcre2_match_data_create_from_pattern(compiled.value().get(), nullptr),
		&pcre2_match_data_free);
	if (!match_data) {
		return false_sentinel;
	}

	const std::string &text = subject.native_value();
	PCRE2_SIZE start_offset = normalize_match_offset(offset, text.size());
	vector_t<vector_t<string_t>> out;
	if (start_offset > text.size()) {
		return out;
	}

	while (start_offset <= text.size()) {
		const int rc = pcre2_match(
			compiled.value().get(),
			reinterpret_cast<PCRE2_SPTR>(text.c_str()),
			text.size(),
			start_offset,
			0,
			match_data.get(),
			nullptr);
		if (rc == PCRE2_ERROR_NOMATCH) {
			break;
		}
		if (rc < 0) {
			return false_sentinel;
		}

		out.append(build_match_vector(subject, match_data.get(), rc));
		PCRE2_SIZE *ovector = pcre2_get_ovector_pointer(match_data.get());
		start_offset = next_match_start(ovector[0], ovector[1], text.size());
	}

	return out;
}

result_or_false<vector_t<vector_t<string_t>>> match_all_pattern_order(const string_t &pattern, const string_t &subject) {
	return match_all_pattern_order(pattern, subject, int_t(0));
}

result_or_false<vector_t<vector_t<string_t>>> match_all_pattern_order(const string_t &pattern, const string_t &subject, const int_t &offset) {
	const auto rows = match_all(pattern, subject, offset);
	if (rows.is_false().native_value()) {
		return false_sentinel;
	}
	return transpose_match_rows(rows.value());
}

result_or_false<vector_t<hash_t<string_t, string_t>>> match_all_named(const string_t &pattern, const string_t &subject) {
	return match_all_named(pattern, subject, int_t(0));
}

result_or_false<vector_t<hash_t<string_t, string_t>>> match_all_named(const string_t &pattern, const string_t &subject, const int_t &offset) {
	auto compiled = compile_pattern(pattern);
	if (!compiled.has_value().native_value()) {
		return false_sentinel;
	}

	match_data_ptr match_data(
		pcre2_match_data_create_from_pattern(compiled.value().get(), nullptr),
		&pcre2_match_data_free);
	if (!match_data) {
		return false_sentinel;
	}

	const std::string &text = subject.native_value();
	PCRE2_SIZE start_offset = normalize_match_offset(offset, text.size());
	vector_t<hash_t<string_t, string_t>> out;
	if (start_offset > text.size()) {
		return out;
	}

	while (start_offset <= text.size()) {
		const int rc = pcre2_match(
			compiled.value().get(),
			reinterpret_cast<PCRE2_SPTR>(text.c_str()),
			text.size(),
			start_offset,
			0,
			match_data.get(),
			nullptr);
		if (rc == PCRE2_ERROR_NOMATCH) {
			break;
		}
		if (rc < 0) {
			return false_sentinel;
		}

		out.append(build_named_match_table(subject, compiled.value().get(), match_data.get(), rc));
		PCRE2_SIZE *ovector = pcre2_get_ovector_pointer(match_data.get());
		start_offset = next_match_start(ovector[0], ovector[1], text.size());
	}

	return out;
}

result_or_false<vector_t<string_t>> grep(const string_t &pattern, const vector_t<string_t> &input) {
	auto compiled = compile_pattern(pattern);
	if (!compiled.has_value().native_value()) {
		return false_sentinel;
	}

	match_data_ptr match_data(
		pcre2_match_data_create_from_pattern(compiled.value().get(), nullptr),
		&pcre2_match_data_free);
	if (!match_data) {
		return false_sentinel;
	}

	vector_t<string_t> out;
	for (std::size_t index = 0; index < input.size(); ++index) {
		const auto &candidate = input[index];
		const int rc = pcre2_match(
			compiled.value().get(),
			reinterpret_cast<PCRE2_SPTR>(candidate.native_value().c_str()),
			candidate.native_value().size(),
			0,
			0,
			match_data.get(),
			nullptr);
		if (rc == PCRE2_ERROR_NOMATCH) {
			continue;
		}
		if (rc < 0) {
			return false_sentinel;
		}
		out.append(candidate);
	}

	return out;
}

result_or_false<vector_t<string_t>> filter(const string_t &pattern, const string_t &replacement, const vector_t<string_t> &input) {
	return filter(pattern, replacement, input, int_t(-1));
}

result_or_false<vector_t<string_t>> filter(const string_t &pattern, const string_t &replacement, const vector_t<string_t> &input, const int_t &limit) {
	int_t ignored_count(0);
	return filter(pattern, replacement, input, limit, ignored_count);
}

result_or_false<vector_t<string_t>> filter(const string_t &pattern, const string_t &replacement, const vector_t<string_t> &input, int_t &count) {
	return filter(pattern, replacement, input, int_t(-1), count);
}

result_or_false<vector_t<string_t>> filter(const string_t &pattern, const string_t &replacement, const vector_t<string_t> &input, const int_t &limit, int_t &count) {
	vector_t<string_t> out;
	std::int64_t replacements = 0;
	for (std::size_t index = 0; index < input.size(); ++index) {
		const auto replaced = replace_impl(pattern, replacement, input[index], limit);
		if (replaced.is_false().native_value()) {
			return false_sentinel;
		}
		replacements += static_cast<std::int64_t>(replaced.value().replacements);
		if (replaced.value().replacements == 0u) {
			continue;
		}
		out.append(replaced.value().text);
	}
	count = int_t(replacements);
	return out;
}

result_or_false<string_t> replace_callback(const string_t &pattern, const std::function<string_t(vector_t<string_t>)> &callback, const string_t &subject) {
	return replace_callback(pattern, callback, subject, int_t(-1));
}

result_or_false<string_t> replace_callback(const string_t &pattern, const std::function<string_t(vector_t<string_t>)> &callback, const string_t &subject, const int_t &limit) {
	int_t ignored_count(0);
	return replace_callback(pattern, callback, subject, limit, ignored_count);
}

result_or_false<string_t> replace_callback(const string_t &pattern, const std::function<string_t(vector_t<string_t>)> &callback, const string_t &subject, int_t &count) {
	return replace_callback(pattern, callback, subject, int_t(-1), count);
}

result_or_false<string_t> replace_callback(const string_t &pattern, const std::function<string_t(vector_t<string_t>)> &callback, const string_t &subject, const int_t &limit, int_t &count) {
	const auto native_limit = limit.native_value();
	if (native_limit < 0 && native_limit != -1) {
		throw scpp::ValueError("preg_replace_callback(): negative limit other than -1 is not supported in the first pass");
	}
	if (native_limit == 0) {
		count = int_t(0);
		return subject;
	}

	auto compiled = compile_pattern(pattern);
	if (!compiled.has_value().native_value()) {
		return false_sentinel;
	}

	match_data_ptr match_data(
		pcre2_match_data_create_from_pattern(compiled.value().get(), nullptr),
		&pcre2_match_data_free);
	if (!match_data) {
		return false_sentinel;
	}

	const std::string &text = subject.native_value();
	std::string out;
	out.reserve(text.size());
	PCRE2_SIZE start_offset = 0;
	std::size_t replacements = 0;
	const bool unlimited = (native_limit < 0);
	const std::size_t max_replacements = unlimited ? 0u : static_cast<std::size_t>(native_limit);

	while (start_offset <= text.size()) {
		if (!unlimited && replacements >= max_replacements) {
			break;
		}

		const int rc = pcre2_match(
			compiled.value().get(),
			reinterpret_cast<PCRE2_SPTR>(text.c_str()),
			text.size(),
			start_offset,
			0,
			match_data.get(),
			nullptr);
		if (rc == PCRE2_ERROR_NOMATCH) {
			break;
		}
		if (rc < 0) {
			return false_sentinel;
		}

		PCRE2_SIZE *ovector = pcre2_get_ovector_pointer(match_data.get());
		const PCRE2_SIZE match_start = ovector[0];
		const PCRE2_SIZE match_end = ovector[1];

		out.append(text.substr(start_offset, match_start - start_offset));
		out.append(callback(build_match_vector(subject, match_data.get(), rc)).native_value());
		++replacements;

		start_offset = next_match_start(match_start, match_end, text.size());
		if (start_offset > text.size()) {
			start_offset = text.size();
			break;
		}
	}

	out.append(text.substr(start_offset));
	count = int_t(static_cast<std::int64_t>(replacements));
	return string_t(std::move(out));
}

result_or_false<string_t> replace_callback_array(const hash_t<std::function<string_t(vector_t<string_t>)>, string_t> &callbacks, const string_t &subject) {
	return replace_callback_array(callbacks, subject, int_t(-1));
}

result_or_false<string_t> replace_callback_array(const hash_t<std::function<string_t(vector_t<string_t>)>, string_t> &callbacks, const string_t &subject, const int_t &limit) {
	int_t ignored_count(0);
	return replace_callback_array(callbacks, subject, limit, ignored_count);
}

result_or_false<string_t> replace_callback_array(const hash_t<std::function<string_t(vector_t<string_t>)>, string_t> &callbacks, const string_t &subject, int_t &count) {
	return replace_callback_array(callbacks, subject, int_t(-1), count);
}

result_or_false<string_t> replace_callback_array(const hash_t<std::function<string_t(vector_t<string_t>)>, string_t> &callbacks, const string_t &subject, const int_t &limit, int_t &count) {
	string_t current = subject;
	std::int64_t total_replacements = 0;
	for (auto it = callbacks.begin_entries(); it != callbacks.end_entries(); ++it) {
		const auto entry = *it;
		int_t local_count(0);
		const auto replaced = replace_callback(entry.key(), entry.value_ref(), current, limit, local_count);
		if (replaced.is_false().native_value()) {
			return false_sentinel;
		}
		total_replacements += local_count.native_value();
		current = replaced.value();
	}
	count = int_t(total_replacements);
	return current;
}

result_or_false<string_t> replace(const string_t &pattern, const string_t &replacement, const string_t &subject) {
	return replace(pattern, replacement, subject, int_t(-1));
}

result_or_false<string_t> replace(const string_t &pattern, const string_t &replacement, const string_t &subject, const int_t &limit) {
	int_t ignored_count(0);
	return replace(pattern, replacement, subject, limit, ignored_count);
}

result_or_false<string_t> replace(const string_t &pattern, const string_t &replacement, const string_t &subject, int_t &count) {
	return replace(pattern, replacement, subject, int_t(-1), count);
}

result_or_false<string_t> replace(const string_t &pattern, const string_t &replacement, const string_t &subject, const int_t &limit, int_t &count) {
	const auto replaced = replace_impl(pattern, replacement, subject, limit);
	if (replaced.is_false().native_value()) {
		return false_sentinel;
	}
	count = int_t(static_cast<std::int64_t>(replaced.value().replacements));
	return replaced.value().text;
}

result_or_false<vector_t<string_t>> split(const string_t &pattern, const string_t &subject) {
	return split(pattern, subject, int_t(-1));
}

result_or_false<vector_t<string_t>> split(const string_t &pattern, const string_t &subject, const int_t &limit) {
	return split(pattern, subject, limit, int_t(0));
}

result_or_false<vector_t<string_t>> split(const string_t &pattern, const string_t &subject, const int_t &limit, const int_t &flags) {
	const auto native_limit = limit.native_value();
	if (native_limit < 0 && native_limit != -1) {
		throw scpp::ValueError("preg_split(): negative limit other than -1 is not supported in the first pass");
	}
	const auto native_flags = flags.native_value();
	constexpr std::int64_t split_no_empty = 1;
	constexpr std::int64_t split_delim_capture = 2;
	constexpr std::int64_t split_offset_capture = 4;
	if ((native_flags & split_offset_capture) != 0) {
		throw scpp::ValueError("preg_split(): PREG_SPLIT_OFFSET_CAPTURE is not supported by the regex module yet");
	}

	auto compiled = compile_pattern(pattern);
	if (!compiled.has_value().native_value()) {
		return false_sentinel;
	}

	match_data_ptr match_data(
		pcre2_match_data_create_from_pattern(compiled.value().get(), nullptr),
		&pcre2_match_data_free);
	if (!match_data) {
		return false_sentinel;
	}

	vector_t<string_t> out;
	const std::string &text = subject.native_value();
	PCRE2_SIZE start_offset = 0;
	std::size_t produced_parts = 0;
	const bool unlimited = (native_limit <= 0);
	const std::size_t max_parts = unlimited ? 0u : static_cast<std::size_t>(native_limit);
	const bool no_empty = (native_flags & split_no_empty) != 0;
	const bool delim_capture = (native_flags & split_delim_capture) != 0;

	while (start_offset <= text.size()) {
		if (!unlimited && produced_parts + 1u >= max_parts) {
			break;
		}

		const int rc = pcre2_match(
			compiled.value().get(),
			reinterpret_cast<PCRE2_SPTR>(text.c_str()),
			text.size(),
			start_offset,
			0,
			match_data.get(),
			nullptr);
		if (rc == PCRE2_ERROR_NOMATCH) {
			break;
		}
		if (rc < 0) {
			return false_sentinel;
		}

		PCRE2_SIZE *ovector = pcre2_get_ovector_pointer(match_data.get());
		const PCRE2_SIZE match_start = ovector[0];
		const PCRE2_SIZE match_end = ovector[1];

		const string_t part(text.substr(start_offset, match_start - start_offset));
		if (!no_empty || !part.empty().native_value()) {
			out.append(part);
			++produced_parts;
		}

		if (delim_capture) {
			for (int capture_index = 1; capture_index < rc; ++capture_index) {
				const PCRE2_SIZE capture_start = ovector[capture_index * 2];
				const PCRE2_SIZE capture_end = ovector[capture_index * 2 + 1];
				if (capture_start == PCRE2_UNSET || capture_end == PCRE2_UNSET) {
					if (!no_empty) {
						out.append(string_t(""));
						++produced_parts;
					}
					continue;
				}
				const string_t capture(text.substr(capture_start, capture_end - capture_start));
				if (no_empty && capture.empty().native_value()) {
					continue;
				}
				out.append(capture);
				++produced_parts;
			}
		}

		start_offset = next_match_start(match_start, match_end, text.size());
		if (start_offset > text.size()) {
			break;
		}
	}

	const string_t tail(text.substr(start_offset));
	if (!no_empty || !tail.empty().native_value()) {
		out.append(tail);
	}
	return out;
}

} // namespace scpp::regex
