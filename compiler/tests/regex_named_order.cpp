#include <scpp/regex/preg_match_all_named_columns.hpp>
#include <scpp/regex/preg_match_all_named_rows.hpp>
#include <scpp/regex/preg_match_named.hpp>
#include <scpp/value_error.hpp>
#include <cassert>

template<class Call>
void requires_flag_error(Call call) {
    try { call(); }
    catch (const scpp::ValueError&) { return; }
    assert(false);
}

int main() {
    using namespace scpp;
    const string invalid{"/[/"}, subject{"a"}, key{"keep"};
    hash_string_string row;
    row.set(key, subject);
    vector_hash_string_string rows;
    rows.append(row);
    vector_string column;
    column.append(subject);
    hash_vector_string_string columns;
    columns.set(key, column);

    requires_flag_error([&] { (void)regex::preg_match_named(invalid, subject, row, 2); });
    requires_flag_error([&] { (void)regex::preg_match_all_named_rows(invalid, subject, rows); });
    requires_flag_error([&] { (void)regex::preg_match_all_named_rows(invalid, subject, rows, 1); });
    requires_flag_error([&] { (void)regex::preg_match_all_named_columns(invalid, subject, columns, 2); });
    assert(row.size() == 1 && row.at(key) == subject);
    assert(rows.size() == 1 && rows.at(0L).at(key) == subject);
    assert(columns.size() == 1 && columns.at(key).at(0L) == subject);
}
