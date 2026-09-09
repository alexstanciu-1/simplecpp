#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/detail.hpp"
#include "scpp/float_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/result.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/weak_p.hpp"

#include <algorithm>
#include <cstdint>
#include <exception>
#include <iomanip>
#include <iostream>
#include <sstream>
#include <stdexcept>
#include <string>
#include <type_traits>
#include <typeinfo>
#include <unordered_set>

namespace scpp {

inline const int_t<> DBG_TYPE{1};
inline const int_t<> DBG_VALUE{1 << 1};
inline const int_t<> DBG_SHAPE{1 << 2};
inline const int_t<> DBG_FIELDS{1 << 3};
inline const int_t<> DBG_KEYS{1 << 4};
inline const int_t<> DBG_LEN{1 << 5};
inline const int_t<> DBG_SOURCE{1 << 6};
inline const int_t<> DBG_CALLER{1 << 7};
inline const int_t<> DBG_JSON{1 << 8};
inline const int_t<> DBG_RAW{1 << 9};
inline const int_t<> DBG_PTR{1 << 10};
inline const int_t<> DBG_COMPACT{1 << 11};

inline const int_t<> DBG_DEPTH_0{1 << 16};
inline const int_t<> DBG_DEPTH_1{1 << 17};
inline const int_t<> DBG_DEPTH_2{1 << 18};
inline const int_t<> DBG_DEPTH_3{1 << 19};
inline const int_t<> DBG_DEPTH_4{1 << 20};
inline const int_t<> DBG_DEPTH_5{1 << 21};

inline const int_t<> DBG_DEFAULT{
	DBG_SOURCE.native_value()
	| DBG_CALLER.native_value()
	| DBG_TYPE.native_value()
	| DBG_VALUE.native_value()
	| DBG_SHAPE.native_value()
	| DBG_DEPTH_2.native_value()
};

namespace php {

namespace dbg_detail {

struct dbg_options_t final {
	std::int64_t flags = DBG_DEFAULT.native_value();
	int depth = 2;
	bool compact = false;
};

struct dbg_state_t final {
	dbg_options_t options;
	std::unordered_set<const void*> seen;
	int max_items = 8;
	int max_fields = 12;
	int max_string_preview = 160;
};

[[nodiscard]] dbg_options_t normalize_options(int_t<> flags);
[[nodiscard]] bool has_flag(const dbg_options_t &options, int_t<> flag) noexcept;
[[nodiscard]] std::string ptr_label(const void *ptr);
[[nodiscard]] std::string escape_preview(std::string_view value, std::size_t limit);
[[nodiscard]] std::string mixed_kind_name(mixed_t::kind_t kind);
void print_header(const char *source_file, int source_line, const char *label, const dbg_options_t &options);
void print_line(int indent, const std::string &line);
void dbg_set_gate(const std::string &key);
void dbg_unset_gate(const std::string &key);
[[nodiscard]] bool dbg_gate_enabled(const std::string &key);

template <typename T>
[[nodiscard]] std::string type_name() {
	if constexpr (std::is_same_v<detail::remove_cvref_t<T>, null_t>) return "null_t";
	else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, nullopt_t>) return "nullopt_t";
	else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, nullptr_t>) return "nullptr_t";
	else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, bool_t>) return "bool_t";
	else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, int_t<>>) return "int_t";
	else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, float_t>) return "float_t";
	else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, string_t>) return "string_t";
	else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, mixed_t>) return "mixed_t";
	else return typeid(detail::remove_cvref_t<T>).name();
}

template <typename T>
[[nodiscard]] std::string inline_value(const T &value);

template <typename T>
void describe_value(const T &value, dbg_state_t &state, int indent, int depth);

template <typename T>
void describe_scalar(const char *kind, const std::string &value, dbg_state_t &state, int indent) {
	if (has_flag(state.options, DBG_TYPE)) {
		print_line(indent, std::string("type: ") + kind);
	}
	if (has_flag(state.options, DBG_VALUE)) {
		print_line(indent, std::string("value: ") + value);
	}
}

template <typename T>
[[nodiscard]] std::string inline_value(const T &value) {
	if constexpr (std::is_same_v<detail::remove_cvref_t<T>, null_t>
		|| std::is_same_v<detail::remove_cvref_t<T>, nullopt_t>
		|| std::is_same_v<detail::remove_cvref_t<T>, nullptr_t>) {
		(void)value;
		return "null";
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, bool_t>) {
		return value.native_value() ? "true" : "false";
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, int_t<>>) {
		return std::to_string(value.native_value());
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, float_t>) {
		std::ostringstream out;
		out << value.native_value();
		return out.str();
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, string_t>) {
		return "string len=" + std::to_string(value.native_value().size()) + " \"" + escape_preview(value.native_value(), 80) + "\"";
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, mixed_t>) {
		return "mixed " + mixed_kind_name(value.kind());
	} else {
		(void)value;
		return "<not inspectable>";
	}
}

template <typename K>
[[nodiscard]] std::string key_preview(const K &key) {
	if constexpr (std::is_same_v<detail::remove_cvref_t<K>, int_t<>>) {
		return std::to_string(key.native_value());
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<K>, string_t>) {
		return "\"" + escape_preview(key.native_value(), 80) + "\"";
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<K>, mixed_t>) {
		switch (key.kind()) {
			case mixed_t::kind_t::null_v:
				return "null";
			case mixed_t::kind_t::bool_v:
				return key.bool_value().native_value() ? "true" : "false";
			case mixed_t::kind_t::int_v:
				return std::to_string(key.int_value().native_value());
			case mixed_t::kind_t::float_v: {
				std::ostringstream out;
				out << key.float_value().native_value();
				return out.str();
			}
			case mixed_t::kind_t::string_v: {
				const auto *text = key.string_if();
				return "\"" + escape_preview(text == nullptr ? std::string_view() : std::string_view(text->native_value()), 80) + "\"";
			}
			default:
				return inline_value(key);
		}
	} else {
		return "<key>";
	}
}

template <typename T, typename K>
void describe_hash(const hash_t<T, K> &value, dbg_state_t &state, int indent, int depth) {
	const void *identity = static_cast<const void*>(&value);
	const bool show_ptr = has_flag(state.options, DBG_PTR);
	std::string head = "hash len=" + std::to_string(value.size());
	if (show_ptr) head += " " + ptr_label(identity);
	if (has_flag(state.options, DBG_TYPE)
		|| has_flag(state.options, DBG_LEN)
		|| has_flag(state.options, DBG_VALUE)
		|| has_flag(state.options, DBG_SHAPE)
		|| has_flag(state.options, DBG_KEYS)
		|| has_flag(state.options, DBG_FIELDS)) {
		print_line(indent, head);
	}
	if (!(has_flag(state.options, DBG_SHAPE) || has_flag(state.options, DBG_KEYS) || has_flag(state.options, DBG_FIELDS)) || depth <= 0) {
		return;
	}
	if (state.seen.count(identity) != 0U) {
		print_line(indent + 1, "<seen " + ptr_label(identity) + ">");
		return;
	}
	state.seen.insert(identity);
	int count = 0;
	value.debug_visit_entries([&](const K &key, const T &entry) {
		if (count >= state.max_items) return;
		if (has_flag(state.options, DBG_KEYS) && !has_flag(state.options, DBG_SHAPE)) {
			print_line(indent + 1, "[" + key_preview(key) + "]");
		} else {
			print_line(indent + 1, "[" + key_preview(key) + "]: " + inline_value(entry));
			describe_value(entry, state, indent + 2, depth - 1);
		}
		++count;
	});
	if (static_cast<std::size_t>(count) < value.size()) {
		print_line(indent + 1, "... " + std::to_string(value.size() - static_cast<std::size_t>(count)) + " more");
	}
}

template <typename T>
void describe_value(const vector_t<T> &value, dbg_state_t &state, int indent, int depth) {
	const void *identity = static_cast<const void*>(&value);
	std::string head = "vector len=" + std::to_string(value.size());
	if (has_flag(state.options, DBG_PTR)) head += " " + ptr_label(identity);
	print_line(indent, head);
	if (!has_flag(state.options, DBG_SHAPE) || depth <= 0) {
		return;
	}
	if (state.seen.count(identity) != 0U) {
		print_line(indent + 1, "<seen " + ptr_label(identity) + ">");
		return;
	}
	state.seen.insert(identity);
	const auto &native = value.native_value();
	const auto limit = std::min<std::size_t>(native.size(), static_cast<std::size_t>(state.max_items));
	for (std::size_t i = 0; i < limit; ++i) {
		print_line(indent + 1, "[" + std::to_string(i) + "]: " + inline_value(native[i]));
		describe_value(native[i], state, indent + 2, depth - 1);
	}
	if (limit < native.size()) {
		print_line(indent + 1, "... " + std::to_string(native.size() - limit) + " more");
	}
}

template <typename T>
void describe_handle(const char *kind, const T *ptr, dbg_state_t &state, int indent, int depth) {
	std::string head = std::string(kind) + "<" + typeid(T).name() + ">";
	if (ptr == nullptr) {
		print_line(indent, head + " null");
		return;
	}
	if (has_flag(state.options, DBG_PTR)) head += " " + ptr_label(ptr);
	print_line(indent, head);
	if (depth <= 0) return;
	if (state.seen.count(ptr) != 0U) {
		print_line(indent + 1, "<seen " + ptr_label(ptr) + ">");
		return;
	}
	state.seen.insert(ptr);
	if (has_flag(state.options, DBG_FIELDS) || has_flag(state.options, DBG_SHAPE)) {
		print_line(indent + 1, "<fields not inspectable>");
	}
}

template <typename T>
void describe_value(const shared_p<T> &value, dbg_state_t &state, int indent, int depth) {
	describe_handle("shared_p", value.get(), state, indent, depth);
}

template <typename T>
void describe_value(const unique_p<T> &value, dbg_state_t &state, int indent, int depth) {
	describe_handle("unique_p", value.get(), state, indent, depth);
}

template <typename T>
void describe_value(const weak_p<T> &value, dbg_state_t &state, int indent, int depth) {
	const auto locked = value.lock();
	describe_handle("weak_p", locked.get(), state, indent, depth);
}

template <typename T>
void describe_value(const nullable<T> &value, dbg_state_t &state, int indent, int depth) {
	if (!value.has_value().native_value()) {
		print_line(indent, "nullable empty");
		return;
	}
	print_line(indent, "nullable value: " + inline_value(value.value()));
	if (depth > 0) describe_value(value.value(), state, indent + 1, depth - 1);
}

template <typename T>
void describe_value(const result_or_false<T> &value, dbg_state_t &state, int indent, int depth) {
	if (!value.has_value().native_value()) {
		print_line(indent, "result_or_false false");
		return;
	}
	print_line(indent, "result_or_false value: " + inline_value(value.value()));
	if (depth > 0) describe_value(value.value(), state, indent + 1, depth - 1);
}

template <typename T>
void describe_value(const result_or_bool<T> &value, dbg_state_t &state, int indent, int depth) {
	if (!value.has_value().native_value()) {
		print_line(indent, std::string("result_or_bool sentinel: ") + (value.is_true().native_value() ? "true" : "false"));
		return;
	}
	print_line(indent, "result_or_bool value: " + inline_value(value.value()));
	if (depth > 0) describe_value(value.value(), state, indent + 1, depth - 1);
}

template <typename T>
void describe_value(const result<T> &value, dbg_state_t &state, int indent, int depth) {
	if (!value.has_value().native_value()) {
		print_line(indent, "result error");
		return;
	}
	print_line(indent, "result value: " + inline_value(value.value()));
	if (depth > 0) describe_value(value.value(), state, indent + 1, depth - 1);
}

void describe_value(const mixed_t &value, dbg_state_t &state, int indent, int depth);

template <typename T, typename K>
void describe_value(const hash_t<T, K> &value, dbg_state_t &state, int indent, int depth) {
	describe_hash(value, state, indent, depth);
}

template <typename T>
void describe_value(const T &value, dbg_state_t &state, int indent, int) {
	if constexpr (std::is_same_v<detail::remove_cvref_t<T>, null_t>
		|| std::is_same_v<detail::remove_cvref_t<T>, nullopt_t>
		|| std::is_same_v<detail::remove_cvref_t<T>, nullptr_t>) {
		(void)value;
		describe_scalar<T>("null_t", "null", state, indent);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, bool_t>) {
		describe_scalar<T>("bool_t", value.native_value() ? "true" : "false", state, indent);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, int_t<>>) {
		describe_scalar<T>("int_t", std::to_string(value.native_value()), state, indent);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, float_t>) {
		std::ostringstream out;
		out << value.native_value();
		describe_scalar<T>("float_t", out.str(), state, indent);
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, string_t>) {
		if (has_flag(state.options, DBG_TYPE)) print_line(indent, "type: string_t");
		if (has_flag(state.options, DBG_LEN)) print_line(indent, "len: " + std::to_string(value.native_value().size()));
		if (has_flag(state.options, DBG_VALUE)) {
			print_line(indent, "value: \"" + escape_preview(value.native_value(), static_cast<std::size_t>(state.max_string_preview)) + "\"");
		}
	} else if constexpr (std::is_same_v<detail::remove_cvref_t<T>, bool>) {
		describe_scalar<T>("bool", value ? "true" : "false", state, indent);
	} else if constexpr (std::is_integral_v<detail::remove_cvref_t<T>>) {
		describe_scalar<T>("native-int", std::to_string(value), state, indent);
	} else if constexpr (std::is_floating_point_v<detail::remove_cvref_t<T>>) {
		std::ostringstream out;
		out << value;
		describe_scalar<T>("native-float", out.str(), state, indent);
	} else if constexpr (std::is_convertible_v<T, std::string_view>) {
		std::string_view text = value;
		if (has_flag(state.options, DBG_TYPE)) print_line(indent, "type: string-like");
		if (has_flag(state.options, DBG_VALUE)) print_line(indent, "value: \"" + escape_preview(text, static_cast<std::size_t>(state.max_string_preview)) + "\"");
	} else {
		(void)value;
		if (has_flag(state.options, DBG_TYPE)) print_line(indent, "type: " + type_name<T>());
		print_line(indent, "<not inspectable>");
	}
}

template <typename T>
void dbg_emit(const char *source_file, int source_line, const char *label, const T &value, int_t<> flags) {
	try {
		dbg_state_t state;
		state.options = normalize_options(flags);
		if (has_flag(state.options, DBG_COMPACT)) {
			state.max_items = 4;
			state.max_fields = 6;
			state.max_string_preview = 80;
		}
		print_header(source_file, source_line, label, state.options);
		describe_value(value, state, 1, state.options.depth);
	} catch (const std::exception &e) {
		std::cout << "[dbg] <debug inspection failed: " << e.what() << ">\n";
	} catch (...) {
		std::cout << "[dbg] <debug inspection failed>\n";
	}
}

} // namespace dbg_detail

void dbg_set(const string_t &key, bool_t enabled = bool_t(true));
void dbg_set(const char *key, bool_t enabled = bool_t(true));
void dbg_unset(const string_t &key, bool_t enabled = bool_t(true));
void dbg_unset(const char *key, bool_t enabled = bool_t(true));
[[nodiscard]] bool_t dbg_enabled(const string_t &key);
[[nodiscard]] bool_t dbg_enabled(const char *key);
void __scpp_debug_call_entry();

template <typename T>
void dbg_at(const char *source_file, int source_line, const T &value) {
	dbg_detail::dbg_emit(source_file, source_line, nullptr, value, DBG_DEFAULT);
}

template <typename T>
void dbg_at(const char *source_file, int source_line, const string_t &label, const T &value) {
	dbg_detail::dbg_emit(source_file, source_line, label.native_value().c_str(), value, DBG_DEFAULT);
}

template <typename T>
void dbg_at(const char *source_file, int source_line, const char *label, const T &value) {
	dbg_detail::dbg_emit(source_file, source_line, label, value, DBG_DEFAULT);
}

template <typename T>
void dbg_at(const char *source_file, int source_line, const T &value, int_t<> flags) {
	dbg_detail::dbg_emit(source_file, source_line, nullptr, value, flags);
}

template <typename T>
void dbg_at(const char *source_file, int source_line, const string_t &label, const T &value, int_t<> flags) {
	dbg_detail::dbg_emit(source_file, source_line, label.native_value().c_str(), value, flags);
}

template <typename T>
void dbg_at(const char *source_file, int source_line, const char *label, const T &value, int_t<> flags) {
	dbg_detail::dbg_emit(source_file, source_line, label, value, flags);
}

template <typename... Args>
void dbg(Args&&... args) {
	dbg_at("", 0, std::forward<Args>(args)...);
}

template <typename... Args>
void dbg_if_at(const string_t &key, const char *source_file, int source_line, Args&&... args) {
	if (dbg_enabled(key).native_value()) {
		dbg_at(source_file, source_line, std::forward<Args>(args)...);
	}
}

template <typename... Args>
void dbg_if_at(const char *key, const char *source_file, int source_line, Args&&... args) {
	if (dbg_enabled(key).native_value()) {
		dbg_at(source_file, source_line, std::forward<Args>(args)...);
	}
}

template <typename... Args>
void dbg_if(const string_t &key, Args&&... args) {
	dbg_if_at(key, "", 0, std::forward<Args>(args)...);
}

template <typename... Args>
void dbg_if(const char *key, Args&&... args) {
	dbg_if_at(key, "", 0, std::forward<Args>(args)...);
}

template <typename T>
void __scpp_debug_dump_at(const char *source_file, int source_line, const string_t &phase, const string_t &label, const T &value) {
	std::cerr
		<< "__SCPP_DEBUG_EVENT__ "
		<< "{\"event\":\"dump\",\"body\":{\"subject\":{\"kind\":\"injected_expr\",\"text\":\""
		<< ::scpp::runtime_error_json_escape(label.native_value())
		<< "\"},\"phase\":\""
		<< ::scpp::runtime_error_json_escape(phase.native_value())
		<< "\",\"value\":{\"type\":\""
		<< ::scpp::runtime_error_json_escape(dbg_detail::type_name<T>())
		<< "\",\"preview\":\""
		<< ::scpp::runtime_error_json_escape(dbg_detail::inline_value(value))
		<< "\"}},\"source\":{\"file\":\""
		<< ::scpp::runtime_error_json_escape(source_file != nullptr ? source_file : "")
		<< "\",\"line\":"
		<< source_line
		<< "}}"
		<< "\n";
}

template <typename T>
void __scpp_debug_dump_at(const char *source_file, int source_line, const char *phase, const char *label, const T &value) {
	__scpp_debug_dump_at(source_file, source_line, string_t(phase), string_t(label), value);
}

template <typename T>
void __scpp_debug_dump_at(const char *source_file, int source_line, const char *phase, const string_t &label, const T &value) {
	__scpp_debug_dump_at(source_file, source_line, string_t(phase), label, value);
}

template <typename T>
void __scpp_debug_dump_at(const char *source_file, int source_line, const string_t &phase, const char *label, const T &value) {
	__scpp_debug_dump_at(source_file, source_line, phase, string_t(label), value);
}

inline void __scpp_debug_exit_at(const char *source_file, int source_line) {
	std::cerr
		<< "__SCPP_DEBUG_EVENT__ "
		<< "{\"event\":\"hit\",\"body\":{\"action_kind\":\"exit\",\"phase\":\"before\"},\"source\":{\"file\":\""
		<< ::scpp::runtime_error_json_escape(source_file != nullptr ? source_file : "")
		<< "\",\"line\":"
		<< source_line
		<< "}}"
		<< "\n";
	std::cerr
		<< "__SCPP_DEBUG_EVENT__ "
		<< "{\"event\":\"exit\",\"body\":{\"reason\":\"action_exit\",\"action_kind\":\"exit\"},\"source\":{\"file\":\""
		<< ::scpp::runtime_error_json_escape(source_file != nullptr ? source_file : "")
		<< "\",\"line\":"
		<< source_line
		<< "}}"
		<< "\n";
	std::exit(0);
}

inline void __scpp_debug_break_at(const char *source_file, int source_line) {
	std::cerr
		<< "__SCPP_DEBUG_EVENT__ "
		<< "{\"event\":\"hit\",\"body\":{\"action_kind\":\"break\",\"phase\":\"before\"},\"source\":{\"file\":\""
		<< ::scpp::runtime_error_json_escape(source_file != nullptr ? source_file : "")
		<< "\",\"line\":"
		<< source_line
		<< "}}"
		<< "\n";
	std::cerr
		<< "__SCPP_DEBUG_EVENT__ "
		<< "{\"event\":\"break\",\"body\":{\"reason\":\"action_break\",\"action_kind\":\"break\"},\"source\":{\"file\":\""
		<< ::scpp::runtime_error_json_escape(source_file != nullptr ? source_file : "")
		<< "\",\"line\":"
		<< source_line
		<< "}}"
		<< "\n";
	std::exit(0);
}

} // namespace php
} // namespace scpp
