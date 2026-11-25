// Observe failed stores through reference parameters without adding source exception syntax.
#include <stdexcept>
#include <string_view>
#include <initializer_list>
using byte_rep = __UINT8_TYPE__;
using int_rep = __INT64_TYPE__;
using operation = void (*)(byte_rep&, int_rep);
struct failure_case { operation run; byte_rep initial; int_rep rhs; };
int main() {
    for (auto test : {failure_case{@add@,250,10}, {@add@,0,-1},
            {@subtract@,0,1}, {@subtract@,255,-1}, {@multiply@,128,2},
            {@multiply@,1,-1}, {@divide@,1,-1}, {@header_init@,250,10}}) {
        byte_rep value = test.initial;
        bool failed = false;
        try { test.run(value,test.rhs); }
        catch (const std::out_of_range& e) { failed = std::string_view(e.what()) == "integer_cast_out_of_range"; }
        if (!failed || value != test.initial) return 90;
    }
    for (auto run : {operation{@divide@}, operation{@remainder@}}) {
        byte_rep value = 8;
        bool failed = false;
        try { run(value,0); } catch (const std::domain_error&) { failed = true; }
        if (!failed || value != 8) return 91;
    }
    // RHS is not prematurely narrowed; only the calculated result is checked.
    byte_rep value = 255;
    @divide@(value,256);
    if (value != 0) return 92;
    value = 255;
    @remainder@(value,256);
    if (value != 255) return 93;
    @add@(value,-255);
    if (value != 0) return 94;
    @subtract@(value,-255);
    if (value != 255) return 95;
    value = 10;
    try { @mutate_then_fail@(value); return 96; } catch (const std::out_of_range&) {}
    if (value != 11) return 97; // RHS ran once; its side effect survives failed write-back.
    value = 10;
    try { @rhs_failure@(value); return 98; } catch (const std::domain_error&) {}
    if (value != 11) return 99;
    value = 254;
    try { @header_update@(value,1); return 100; } catch (const std::out_of_range&) {}
    if (value != 255) return 101;
    return 8;
}
