// Private implementation included in the regex archive, after the PCRE2 capture utilities.
enum class capture_order { single, rows, columns, both };

template<class Container>
Container capture_container() {
    if constexpr (std::is_same_v<Container, mixed>) return mixed::container();
    else return {};
}

template<int Flags, class Cell>
Cell capture_value(std::string_view subject, const PCRE2_SIZE* bounds) {
    const bool present = bounds && bounds[0] != PCRE2_UNSET;
    const int64 start = present ? static_cast<int64>(bounds[0]) : -1;
    string text = present ? owned(subject.substr(bounds[0], bounds[1] - bounds[0])) : string{};
    if constexpr (std::is_same_v<Cell, mixed>) {
        mixed value = present || !(Flags & unmatched_as_null) ? mixed::from_string(text) : mixed{};
        if constexpr (!(Flags & capture_offsets)) return value;
        auto pair = mixed::container();
        pair.append(std::move(value));
        pair.append(mixed::from_integer(start));
        return pair;
    } else if constexpr (Flags & capture_offsets) {
        return Cell{std::move(text), start};
    } else {
        return present ? nullable_string{std::move(text)} : nullable_string{};
    }
}

template<bool Named, class Output, class Value>
void write_capture(Output& out, int64 index, const std::vector<string>& names, bool participating, Value value) {
    if constexpr (Named) {
        const auto& name = names[index];
        if (name.size() && (participating || !out.has(name))) out.set(name, value);
        if constexpr (std::is_same_v<Output, mixed>) out.set(index, std::move(value));
        else out.set(capture_key(index), std::move(value));
    } else out.append(std::move(value));
}

template<capture_order Order, bool Named, int Flags, class Cell, class Row, class Column, class Output>
result_or_false_int64 collect_flagged(const string& pattern, const string& subject, Output& matches, int64 flags, int64 offset) {
    const int64 order = flags & ~capture_modifiers;
    if ((flags & capture_modifiers) != Flags) throw ValueError("capture flags do not match the output type");
    validate_match_flags(order, Order != capture_order::single);
    if constexpr (Order == capture_order::both) {
        if (order == 2) return collect_flagged<capture_order::rows, Named, Flags, Cell, Row, Column>(pattern, subject, matches, flags, offset);
        return collect_flagged<capture_order::columns, Named, Flags, Cell, Row, Column>(pattern, subject, matches, flags, offset);
    } else {
        if constexpr (Order == capture_order::rows)
            if (order != 2) throw ValueError("capture rows require PREG_SET_ORDER");
        if constexpr (Order == capture_order::columns)
            if (order == 2) throw ValueError("capture columns require PREG_PATTERN_ORDER");
        capture_pattern compiled(pattern);
        if (compiled.data) {
            const auto names = Named ? names_by_group(compiled) : std::vector<string>{};
            const size_t groups = pcre2_get_ovector_count(compiled.data.get());
            auto output = capture_container<Output>();
            std::vector<Column> columns;
            if constexpr (Order == capture_order::columns) {
                columns.reserve(groups);
                for (size_t i = 0; i < groups; ++i) columns.push_back(capture_container<Column>());
            }
            const auto text = bytes(subject);
            int64 hits = 0;
            const bool ok = compiled.each(text, capture_offset(offset, subject.size()), [&](int count, size_t, size_t) {
                const auto* pairs = pcre2_get_ovector_pointer(compiled.data.get());
                const auto width = (Flags & unmatched_as_null) || Order == capture_order::columns ? groups : static_cast<size_t>(count);
                Row row;
                if constexpr (Order != capture_order::columns) row = capture_container<Row>();
                for (size_t i = 0; i < width; ++i) {
                    const auto* bounds = i < static_cast<size_t>(count) ? pairs + 2 * i : nullptr;
                    auto cell = capture_value<Flags, Cell>(text, bounds);
                    if constexpr (Order == capture_order::columns) columns[i].append(std::move(cell));
                    else write_capture<Named>(row, i, names, bounds && bounds[0] != PCRE2_UNSET, std::move(cell));
                }
                if constexpr (Order == capture_order::single) output = std::move(row);
                if constexpr (Order == capture_order::rows) output.append(std::move(row));
                ++hits;
                return Order != capture_order::single;
            });
            if (ok) {
                if constexpr (Order == capture_order::columns) {
                    for (size_t i = 0; i < columns.size(); ++i)
                        write_capture<Named>(output, i, names, true, std::move(columns[i]));
                }
                matches = std::move(output);
                return result_or_false_int64{hits};
            }
        }
        matches = capture_container<Output>();
        return result_or_false_int64::from_false();
    }
}

template<int Flags>
result_or_false_int64 boxed_captures(const string& pattern, const string& subject, mixed& matches, int64 flags, int64 offset, bool all) {
    if (all) return collect_flagged<capture_order::both, true, Flags, mixed, mixed, mixed>(pattern, subject, matches, flags, offset);
    return collect_flagged<capture_order::single, true, Flags, mixed, mixed, mixed>(pattern, subject, matches, flags, offset);
}

result_or_false_int64 flagged_mixed_matches(const string& pattern, const string& subject, mixed& matches, int64 flags, int64 offset, bool all) {
    switch (flags & capture_modifiers) {
        case capture_offsets: return boxed_captures<capture_offsets>(pattern, subject, matches, flags, offset, all);
        case unmatched_as_null: return boxed_captures<unmatched_as_null>(pattern, subject, matches, flags, offset, all);
        default: return boxed_captures<capture_modifiers>(pattern, subject, matches, flags, offset, all);
    }
}
