#include "public_api_guard.hpp"
#include "scpp/debug/flags.hpp"
#include "scpp/debug/dbg_value_int64.hpp"
#include "scpp/debug/dbg_value_float64.hpp"
#include "scpp/debug/dbg_value_boolean.hpp"
#include "scpp/debug/dbg_value_string.hpp"
#include "scpp/debug/dbg_labelled_int64.hpp"
#include "scpp/debug/dbg_labelled_float64.hpp"
#include "scpp/debug/dbg_labelled_boolean.hpp"
#include "scpp/debug/dbg_labelled_string.hpp"
#include <cstdio>
#include <cstdlib>
#include <iostream>
#include <new>
#include <sstream>
#include <string>
#include <utility>

static unsigned debug_allocations = 0;
void *operator new(std::size_t size)
{
    ++debug_allocations;
    if (auto memory = std::malloc(size ? size : 1)) return memory;
    throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }
void emit_native_debug(int kind, bool labelled, long long flags);
#define CHECK(value) do { if (!(value)) { std::fprintf(stderr, "debug failure %d\n", __LINE__); std::abort(); } } while(false)

static void emit_public_debug(int kind, bool labelled, long long flags)
{
    using namespace scpp;
    const string label("sample");
    const string text("a\n\0b", uint64(4));
    if (!labelled) {
        switch (kind) {
            case 0: debug::dbg_at("fixture.phs", int32(42), int64(-7)); break;
            case 1: debug::dbg_at("fixture.phs", int32(42), float64(1.25)); break;
            case 2: debug::dbg_at("fixture.phs", int32(42), boolean(true)); break;
            case 3: debug::dbg_at("fixture.phs", int32(42), text); break;
        }
    } else {
        const int64 options(flags);
        switch (kind) {
            case 0: debug::dbg_at("fixture.phs", int32(42), label, int64(-7), options); break;
            case 1: debug::dbg_at("fixture.phs", int32(42), label, float64(1.25), options); break;
            case 2: debug::dbg_at("fixture.phs", int32(42), label, boolean(true), options); break;
            case 3: debug::dbg_at("fixture.phs", int32(42), label, text, options); break;
        }
    }
}

struct output_redirect final {
    std::streambuf *previous;
    explicit output_redirect(std::streambuf *buffer): previous(std::cout.rdbuf(buffer)) {}
    ~output_redirect() { std::cout.rdbuf(this->previous); }
};

static std::pair<std::string, unsigned> capture(bool native, int kind, bool labelled, long long flags)
{
    std::ostringstream output;
    output_redirect redirect(output.rdbuf());
    const auto before = debug_allocations;
    if (native) emit_native_debug(kind, labelled, flags);
    else emit_public_debug(kind, labelled, flags);
    const auto used = debug_allocations - before;
    return {output.str(), used};
}

int main()
{
    using namespace scpp::debug;
    const long long options[] = {
        0,
        DBG_SOURCE.native_value() | DBG_VALUE.native_value() | DBG_LEN.native_value(),
        DBG_TYPE.native_value() | DBG_COMPACT.native_value(),
        DBG_DEPTH_1.native_value() | DBG_DEPTH_2.native_value()
    };
    for (int kind = 0; kind < 4; ++kind) {
        for (int form = 0; form < 5; ++form) {
            const bool labelled = form != 0;
            const auto flags = labelled ? options[form-1] : 0;
            (void)capture(true, kind, labelled, flags);
            (void)capture(false, kind, labelled, flags);
            const auto native = capture(true, kind, labelled, flags);
            const auto public_value = capture(false, kind, labelled, flags);
            CHECK(native.first == public_value.first);
            CHECK(native.second == public_value.second);
            if (form == 0) CHECK(public_value.first.find("fixture.phs:42") != std::string::npos);
            if (form == 4) CHECK(public_value.first.find("multiple DBG_DEPTH_*") != std::string::npos);
            if (kind == 3 && form == 2) CHECK(public_value.first.find("a\\n\\x00b") != std::string::npos);
        }
    }
    std::puts("PASS: all scalar debug forms, source context, byte escaping, invalid flags and native/public allocation parity");
}
