#pragma once
#include <scpp/hashes.hpp>
#include <scpp/vectors.hpp>
#include <cassert>
#include <stdexcept>
#include <string_view>

using Int = scpp::int64;
namespace App {
Int record_hash_();
scpp::vector_int64 vector_read_pair_(scpp::vector_int64&);
scpp::vector_int64 hole_read_pair_(scpp::hash_int64_int64&);
scpp::vector_int64 read_pair_(scpp::hash_int64_int64&, Int, Int);
scpp::vector_int64 hash_batch_(scpp::hash_int64_int64&, scpp::hash_int64_int64&, scpp::vector_int64&, Int);
}

static void check_hash_segments() {
    scpp::hash_int64_int64 table;
    scpp::vector_int64 entered;
    entered.append(0);
    entered.append(0);
    for (Int key = 1; key <= 5; ++key) table.set(key, key);
    assert(table.remove(Int{2}));
    table.set(Int{3}, Int{30});
    auto crossing = App::hole_read_pair_(table);
    assert(crossing.at(0) == 101330 && crossing.at(1) == 101330);
    scpp::vector_int64 values;
    for (Int value : {9, 4, 7, 2}) values.append(value);
    auto middle = App::vector_read_pair_(values);
    assert(middle.at(0) == 47 && middle.at(1) == 47);
    auto deleted = App::read_pair_(table, 0, 4);
    assert(deleted.at(0) == 1345 && deleted.at(1) == 4);
    table.set(Int{2}, Int{20});
    auto reinserted = App::read_pair_(table, 0, 5);
    assert(reinserted.at(0) == 13452 && reinserted.at(1) == 5);
    auto empty = App::read_pair_(table, 5, 0);
    assert(empty.at(0) == 0 && empty.at(1) == 0);
    assert(App::record_hash_() == 8);
    try {
        App::hash_batch_(table, table, entered, 2);
        assert(false);
    } catch (const std::invalid_argument& error) {
        assert(std::string_view(error.what()) == "Overlapping read and write segments");
    }
    assert(entered.at(0) == 0 && entered.at(1) == 0);
    assert(table.at(Int{3}) == 30 && table.at(Int{4}) == 4);
    auto results = App::hash_batch_(table, table, entered, 4);
    assert(results.at(0) == 34 && results.at(1) == 2);
    assert(entered.at(0) == 1 && entered.at(1) == 1);
    assert(table.at(Int{3}) == 40 && table.at(Int{4}) == 14);
    assert(table.at(Int{1}) == 1 && table.at(Int{5}) == 5 && table.at(Int{2}) == 20);
}
