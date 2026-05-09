#include "dbg.hpp"

#include <unordered_map>

namespace scpp::php {
namespace dbg_detail {

namespace {

std::unordered_set<std::string> &gate_registry() {
	static std::unordered_set<std::string> gates;
	return gates;
}

[[nodiscard]] int count_depth_flags(std::int64_t flags) {
	int count = 0;
	const std::int64_t depth_flags[] = {
		DBG_DEPTH_0.native_value(),
		DBG_DEPTH_1.native_value(),
		DBG_DEPTH_2.native_value(),
		DBG_DEPTH_3.native_value(),
		DBG_DEPTH_4.native_value(),
		DBG_DEPTH_5.native_value(),
	};
	for (const auto flag : depth_flags) {
		if ((flags & flag) != 0) ++count;
	}
	return count;
}

[[nodiscard]] int selected_depth(std::int64_t flags) {
	if ((flags & DBG_DEPTH_0.native_value()) != 0) return 0;
	if ((flags & DBG_DEPTH_1.native_value()) != 0) return 1;
	if ((flags & DBG_DEPTH_2.native_value()) != 0) return 2;
	if ((flags & DBG_DEPTH_3.native_value()) != 0) return 3;
	if ((flags & DBG_DEPTH_4.native_value()) != 0) return 4;
	if ((flags & DBG_DEPTH_5.native_value()) != 0) return 5;
	return 2;
}

} // namespace

dbg_options_t normalize_options(int_t flags) {
	dbg_options_t options;
	options.flags = flags.native_value();
	if (options.flags == 0) {
		options.flags = DBG_DEFAULT.native_value();
	}
	if (count_depth_flags(options.flags) > 1) {
		throw std::runtime_error("dbg config error: multiple DBG_DEPTH_* flags provided");
	}
	options.depth = selected_depth(options.flags);
	options.compact = (options.flags & DBG_COMPACT.native_value()) != 0;
	return options;
}

bool has_flag(const dbg_options_t &options, int_t flag) noexcept {
	return (options.flags & flag.native_value()) != 0;
}

std::string ptr_label(const void *ptr) {
	if (ptr == nullptr) {
		return "@0x0";
	}
	std::ostringstream out;
	out << "@0x" << std::hex << reinterpret_cast<std::uintptr_t>(ptr);
	return out.str();
}

std::string escape_preview(std::string_view value, std::size_t limit) {
	std::string out;
	const auto size = std::min(value.size(), limit);
	out.reserve(size);
	for (std::size_t i = 0; i < size; ++i) {
		const unsigned char ch = static_cast<unsigned char>(value[i]);
		switch (ch) {
			case '\n': out += "\\n"; break;
			case '\r': out += "\\r"; break;
			case '\t': out += "\\t"; break;
			case '"': out += "\\\""; break;
			case '\\': out += "\\\\"; break;
			default:
				if (ch < 0x20) {
					std::ostringstream hex;
					hex << "\\x" << std::hex << std::setw(2) << std::setfill('0') << static_cast<int>(ch);
					out += hex.str();
				} else {
					out.push_back(static_cast<char>(ch));
				}
		}
	}
	if (value.size() > limit) {
		out += "...";
	}
	return out;
}

std::string mixed_kind_name(mixed_t::kind_t kind) {
	switch (kind) {
		case mixed_t::kind_t::null_v: return "null";
		case mixed_t::kind_t::bool_v: return "bool";
		case mixed_t::kind_t::int_v: return "int";
		case mixed_t::kind_t::float_v: return "float";
		case mixed_t::kind_t::string_v: return "string";
		case mixed_t::kind_t::table_v: return "hash";
		case mixed_t::kind_t::shared_table_v: return "shared_hash";
		case mixed_t::kind_t::dynamic_v: return "dynamic";
		case mixed_t::kind_t::weak_table_v: return "weak_hash";
	}
	return "unknown";
}

void print_header(const char *source_file, int source_line, const char *label, const dbg_options_t &options) {
	std::cout << "[dbg]";
	if (has_flag(options, DBG_SOURCE) && source_file != nullptr && source_file[0] != '\0') {
		std::cout << " " << source_file;
		if (source_line > 0) {
			std::cout << ":" << source_line;
		}
	}
	if (has_flag(options, DBG_CALLER)) {
		std::cout << " in <runtime>";
	}
	if (label != nullptr && label[0] != '\0') {
		std::cout << " " << label;
	}
	std::cout << "\n";
}

void print_line(int indent, const std::string &line) {
	for (int i = 0; i < indent; ++i) {
		std::cout << "  ";
	}
	std::cout << line << "\n";
}

void dbg_set_gate(const std::string &key) {
	if (key.empty()) {
		throw std::runtime_error("dbg_set: gate name must not be empty");
	}
	auto &gates = gate_registry();
	if (gates.count(key) != 0U) {
		throw std::runtime_error("dbg_set: gate already enabled: " + key);
	}
	gates.insert(key);
}

void dbg_unset_gate(const std::string &key) {
	if (key.empty()) {
		throw std::runtime_error("dbg_unset: gate name must not be empty");
	}
	auto &gates = gate_registry();
	if (gates.erase(key) == 0U) {
		throw std::runtime_error("dbg_unset: gate is not enabled: " + key);
	}
}

bool dbg_gate_enabled(const std::string &key) {
	return gate_registry().count(key) != 0U;
}

void describe_value(const mixed_t &value, dbg_state_t &state, int indent, int depth) {
	if (has_flag(state.options, DBG_TYPE)) {
		print_line(indent, "type: mixed_t");
		print_line(indent, "runtime: " + mixed_kind_name(value.kind()));
	}

	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			if (has_flag(state.options, DBG_VALUE)) print_line(indent, "value: null");
			return;
		case mixed_t::kind_t::bool_v:
			if (has_flag(state.options, DBG_VALUE)) print_line(indent, std::string("value: ") + (value.bool_value().native_value() ? "true" : "false"));
			return;
		case mixed_t::kind_t::int_v:
			if (has_flag(state.options, DBG_VALUE)) print_line(indent, "value: " + std::to_string(value.int_value().native_value()));
			return;
		case mixed_t::kind_t::float_v:
			if (has_flag(state.options, DBG_VALUE)) {
				std::ostringstream out;
				out << value.float_value().native_value();
				print_line(indent, "value: " + out.str());
			}
			return;
		case mixed_t::kind_t::string_v: {
			const auto *text = value.string_if();
			const auto preview = text == nullptr ? std::string() : escape_preview(text->native_value(), static_cast<std::size_t>(state.max_string_preview));
			if (has_flag(state.options, DBG_LEN) && text != nullptr) print_line(indent, "len: " + std::to_string(text->native_value().size()));
			if (has_flag(state.options, DBG_VALUE)) print_line(indent, "value: \"" + preview + "\"");
			return;
		}
		case mixed_t::kind_t::table_v: {
			const auto *table = value.table_if();
			if (table == nullptr) {
				print_line(indent, "value: <missing hash storage>");
				return;
			}
			describe_hash(*table, state, indent, depth);
			return;
		}
		case mixed_t::kind_t::shared_table_v: {
			const auto *shared = value.shared_table_if();
			const auto *table = shared == nullptr ? nullptr : shared->get();
			if (table == nullptr) {
				print_line(indent, "shared_hash null");
				return;
			}
			describe_hash(*table, state, indent, depth);
			return;
		}
		case mixed_t::kind_t::dynamic_v: {
			const auto *dynamic = value.dynamic_if();
			const auto *table = dynamic == nullptr ? nullptr : dynamic->get();
			if (table == nullptr) {
				print_line(indent, "dynamic null");
				return;
			}
			describe_hash(*table, state, indent, depth);
			return;
		}
		case mixed_t::kind_t::weak_table_v: {
			const auto *weak = value.weak_table_if();
			const auto locked = weak == nullptr ? shared_p<hash_t<mixed_t>>(null_t{}) : weak->lock();
			const auto *table = locked.get();
			if (table == nullptr) {
				print_line(indent, "weak_hash expired");
				return;
			}
			describe_hash(*table, state, indent, depth);
			return;
		}
	}
}

} // namespace dbg_detail

void dbg_set(const string_t &key, bool_t enabled) {
	if (!enabled.native_value()) return;
	dbg_detail::dbg_set_gate(key.native_value());
}

void dbg_set(const char *key, bool_t enabled) {
	dbg_set(string_t(key), enabled);
}

void dbg_unset(const string_t &key, bool_t enabled) {
	if (!enabled.native_value()) return;
	dbg_detail::dbg_unset_gate(key.native_value());
}

void dbg_unset(const char *key, bool_t enabled) {
	dbg_unset(string_t(key), enabled);
}

bool_t dbg_enabled(const string_t &key) {
	return bool_t(dbg_detail::dbg_gate_enabled(key.native_value()));
}

bool_t dbg_enabled(const char *key) {
	return dbg_enabled(string_t(key));
}

} // namespace scpp::php

