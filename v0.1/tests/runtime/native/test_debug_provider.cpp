#include "scpp/support/dbg.hpp"

void emit_native_debug(int kind, bool labelled, long long flags)
{
    const scpp::string_t label("sample");
    const scpp::string_t text(std::string("a\n\0b", 4));
    if (!labelled) {
        switch (kind) {
            case 0: scpp::php::dbg_at("fixture.phs", 42, scpp::int_t<>(-7)); break;
            case 1: scpp::php::dbg_at("fixture.phs", 42, scpp::float_t(1.25)); break;
            case 2: scpp::php::dbg_at("fixture.phs", 42, scpp::bool_t(true)); break;
            case 3: scpp::php::dbg_at("fixture.phs", 42, text); break;
        }
    } else {
        const scpp::int_t<> options(flags);
        switch (kind) {
            case 0: scpp::php::dbg_at("fixture.phs", 42, label, scpp::int_t<>(-7), options); break;
            case 1: scpp::php::dbg_at("fixture.phs", 42, label, scpp::float_t(1.25), options); break;
            case 2: scpp::php::dbg_at("fixture.phs", 42, label, scpp::bool_t(true), options); break;
            case 3: scpp::php::dbg_at("fixture.phs", 42, label, text, options); break;
        }
    }
}
