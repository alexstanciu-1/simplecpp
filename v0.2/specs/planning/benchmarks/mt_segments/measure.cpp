// Compile with the generated record and vector declarations included by run.py.
#include <scpp/mt_segments.hpp>
#include <scpp/vectors.hpp>
#include "reuse.hpp"
#include <algorithm>
#include <chrono>
#include <cstdlib>
#include <iostream>
#include <stdexcept>

using Int = scpp::int64;
using Stock = App::scpp_records::Stock;
static_assert(sizeof(Stock) == 128);
namespace App {
Int reconcile_(scpp::mt::segment, scpp::mt::segment);
scpp::vector_int64 reconcile_batch_(Rows&, scpp::vector_int64&, Int, Int, Int);
}

int main(int argc, char** argv) {
    if (argc != 2) return 1;
    const Int n = std::atoll(argv[1]);
    const Int split = n / 2;
    Rows rows;
    scpp::vector_int64 changes;
    Int expected[2]{};
    for (Int i = 0; i < n; ++i) {
        Stock s{};
        s.sku = 1000000 + i;
        s.warehouse = i % 8;
        s.reserved = i % 21;
        s.unit_cost_cents = 50 + i % 10000;
        rows.append(s);
        changes.append(0);
        expected[i >= split] += (500 + i % 300 + i % 11 - 5 - s.reserved) * s.unit_cost_cents;
    }
    auto stock = [&](Int i) -> Stock& {
        return *static_cast<Stock*>(scpp::mt::job::borrow(rows, i, 1).at(0));
    };
    bench::pair_executor pool;
    int order[]{0, 1, 2};
    const int rounds = n == 0 ? 120 : 30;
    for (int pair = -6; pair < rounds; ++pair) {
        for (int mode : order) {
            bench::selected = mode == 2 ? &pool : nullptr;
            for (Int i = 0; i < n; ++i) {
                auto& s = stock(i);
                s.on_hand = 500 + i % 300;
                s.available = s.value_cents = s.revision = 0;
                changes.set(i, i % 11 - 5);
            }
            Int subtotals[2];
            const auto start = std::chrono::steady_clock::now();
            if (mode != 0) {
                auto result = App::reconcile_batch_(rows, changes, split, split, n - split);
                subtotals[0] = result.at(0);
                subtotals[1] = result.at(1);
            } else {
                for (int part = 0; part < 2; ++part) {
                    const Int off = part ? split : 0, len = part ? n - split : split;
                    subtotals[part] = App::reconcile_(scpp::mt::job::borrow(rows, off, len),
                        scpp::mt::job::borrow(changes, off, len));
                }
            }
            const auto elapsed = std::chrono::steady_clock::now() - start;
            if (subtotals[0] != expected[0] || subtotals[1] != expected[1]) return 2;
            for (Int i = 0; i < n; ++i) {
                auto& s = stock(i);
                const Int qty = 500 + i % 300 + i % 11 - 5;
                if (s.on_hand != qty || s.available != qty - i % 21 || s.revision != 1 ||
                    s.value_cents != s.available * s.unit_cost_cents || changes.at(i) != 0) return 3;
            }
            if (pair >= 0) std::cout << n << ',' << pair << ',' << (mode == 0 ? "sequential" : mode == 1 ? "fresh" : "reused") << ','
                << std::chrono::duration<double, std::micro>(elapsed).count() << '\n';
        }
        std::next_permutation(order, order + 3);
    }
    if (n == 0) return 0;
    try { App::reconcile_batch_(rows, changes, split, split - 1, n - split); }
    catch (const std::invalid_argument& error) { std::cerr << error.what() << '\n'; return 0; }
    return 4;
}
