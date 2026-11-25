"""Allocation-failure, presizing and lifetime proofs using emitted literal expressions."""
import argparse
import json
import sys
from pathlib import Path
import re
import subprocess
from list_initialization_native import ROOT, includes, run

PRELUDE = r'''
#include <cstdlib>
#include <cstdint>
#include <new>
#include <cassert>
static long budget = -1;
static long live = 0;
static long allocations = 0;
static void* allocate(std::size_t size, std::size_t alignment) {
    if (budget == 0) throw std::bad_alloc();
    if (budget > 0) --budget;
    auto* raw = std::malloc(size + alignment + sizeof(void*));
    if (!raw) throw std::bad_alloc();
    auto address = (reinterpret_cast<std::uintptr_t>(raw) + sizeof(void*) + alignment - 1) & ~(alignment - 1);
    auto* p = reinterpret_cast<void*>(address);
    static_cast<void**>(p)[-1] = raw;
    ++live; ++allocations;
    return p;
}
void* operator new(std::size_t n) { return allocate(n, alignof(std::max_align_t)); }
void* operator new[](std::size_t n) { return ::operator new(n); }
void* operator new(std::size_t n, std::align_val_t a) { return allocate(n, static_cast<std::size_t>(a)); }
void operator delete(void* p) noexcept { if (p) { --live; std::free(static_cast<void**>(p)[-1]); } }
void operator delete[](void* p) noexcept { ::operator delete(p); }
void operator delete(void* p, std::size_t) noexcept { ::operator delete(p); }
void operator delete[](void* p, std::size_t) noexcept { ::operator delete(p); }
void operator delete(void* p, std::align_val_t) noexcept { ::operator delete(p); }
void operator delete(void* p, std::size_t, std::align_val_t) noexcept { ::operator delete(p); }
'''


def expression(fixture):
    return re.search(r'\(\[&\]\(\) \{.*?\}\(\)\)', fixture['cpp'], re.S).group()


def prove(out, runtime, fixtures, cxx, sanitize):
    out.mkdir(parents=True, exist_ok=True)
    text = expression(fixtures['php-strings']).replace('var_text_0', 'source')
    tests = ''
    for count in (1, 3, 511, 512, 513):
        expr = expression(fixtures['php-count-' + str(count)])
        tests += f'''{{ auto before = allocations; auto value = {expr};
            assert(value.size() == {count});
            const auto& storage = scpp::detail::vector_access::read(value);
            assert(storage.capacity == {count});
            assert(reinterpret_cast<std::uintptr_t>(storage.data) % alignof(scpp::int64) == 0);
            assert(allocations - before == 2 + (sizeof(scpp::int64) * {count} > 4096));
            for (scpp::int64 i = 0; i < {count}; ++i) assert(value.at(i) == 7);
        }}\n'''
    source = '#include <cstddef>\n' + PRELUDE + '\n#include "' + str(runtime / 'generated/src/runtime.cpp') + '"\n#include <cstring>\n'
    source += r'''
static bool equal(const scpp::string& a, const scpp::string& b) {
    return a.size() == b.size() && std::memcmp(a.data(), b.data(), static_cast<std::size_t>(a.size())) == 0;
}
int main() {
    const auto initial_live = live;
    {
        auto before = allocations;
        auto empty = scpp::vector_int64::from_owned(nullptr, 0);
        assert(empty.size() == 0 && allocations == before);
    }
'''
    source += tests
    source += r'''
    {
        scpp::string source{"long heap-backed input with a NUL\0after the zero", 48};
        const auto expected = source;
        auto build = [&]() { return EXPRESSION; };
        auto result = build();
        assert(equal(source, expected) && equal(result.at(0), expected) && equal(result.at(1), expected));
        result.set(scpp::int64{0}, scpp::string{"changed", 7});
        assert(equal(source, expected) && equal(result.at(1), expected));
        source = scpp::string{};
        assert(equal(result.at(1), expected));
        source = expected;
        bool success = false;
        for (long allowance = 0; allowance < 64; ++allowance) {
            {
                scpp::vector_string destination;
                destination.append(scpp::string{"keep", 4});
                const auto baseline = live;
                budget = allowance;
                try { destination = build(); budget = -1; success = true; }
                catch (const std::bad_alloc&) {
                    budget = -1;
                    assert(live == baseline);
                    assert(destination.size() == 1 && destination.at(scpp::int64{0}).size() == 4);
                }
                assert(equal(source, expected));
                if (success) assert(equal(destination.at(scpp::int64{0}), expected));
            }
            if (success) break;
        }
        assert(success);
        // The read-only factory copies without modifying its input.
        scpp::string input[] = {expected, expected};
        assert(reinterpret_cast<std::uintptr_t>(input) % alignof(scpp::string) == 0);
        const auto before = allocations;
        auto copied = scpp::vector_string::from_list(input, 2);
        assert(allocations - before == 6); // record + element block + two heap-string copies
        assert(scpp::detail::vector_access::read(copied).capacity == 2);
        bool copied_success = false;
        for (long allowance = 0; allowance < 32; ++allowance) {
            scpp::vector_string destination;
            destination.append(scpp::string{"keep", 4});
            const auto baseline = live;
            budget = allowance;
            try { destination = scpp::vector_string::from_list(input, 2); budget = -1; copied_success = true; }
            catch (const std::bad_alloc&) {
                budget = -1;
                assert(live == baseline && destination.size() == 1 && destination.at(0).size() == 4);
            }
            assert(equal(input[0], expected) && equal(input[1], expected));
            if (copied_success) break;
        }
        assert(copied_success);
        input[0] = scpp::string{};
        assert(equal(copied.at(0), expected) && equal(input[1], expected));
    }
    assert(live == initial_live);
}
'''.replace('EXPRESSION', text)
    # Also sweep every allocation failure through the generated heap-input path.
    heap = expression(fixtures['php-count-513'])
    extra = '''
    { bool success = false;
      for (long allowance = 0; allowance < 16; ++allowance) {
        auto baseline = live; budget = allowance;
        try { { auto value = HEAP; assert(value.size() == 513); } budget = -1; success = true; }
        catch (const std::bad_alloc&) { budget = -1; }
        assert(live == baseline);
        if (success) break;
      }
      assert(success);
    }
'''.replace('HEAP', heap)
    string_heap = expression(fixtures['php-string-source-129']).replace('var_text_0', 'source')
    extra += r'''
    {
      scpp::string source{"long heap-backed source bytes for throwing copies", 48};
      const auto expected = source;
      bool success = false;
      for (long allowance = 0; allowance < 300; ++allowance) {
        auto baseline = live; budget = allowance;
        try { { auto value = STRING_HEAP; assert(value.size() == 129); } budget = -1; success = true; }
        catch (const std::bad_alloc&) { budget = -1; }
        assert(live == baseline && equal(source, expected));
        if (success) break;
      }
      assert(success);
    }
'''.replace('STRING_HEAP', string_heap)
    source = source.replace('    assert(live == initial_live);', extra + '    assert(live == initial_live);')
    path = out / 'proof.cpp'
    path.write_text(source)
    flags = ['-std=c++23', '-O1' if sanitize else '-O0', '-g' if sanitize else '-g0', '-Wall', '-Wextra', '-Werror', *includes(runtime)]
    if sanitize:
        flags += ['-fsanitize=address,undefined', '-fsanitize-address-use-after-scope', '-fno-sanitize-recover=all']
    # The probe embeds generated implementation code. Link only the routines it exercises,
    # since other published carriers may depend on optional module objects.
    if sys.platform.startswith("linux"):
        flags += ["-ffunction-sections", "-fdata-sections", "-Wl,--gc-sections"]
    run([cxx, *flags, path, '-o', out / 'proof'])
    run([out / 'proof'])
    print(cxx, 'PASS allocation failures, exact presizing/no growth, ownership and backing lifetimes', 'ASan/UBSan' if sanitize else '')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('--runtime', type=Path, default=ROOT / 'runtime')
    parser.add_argument('--fixtures', type=Path, required=True)
    parser.add_argument('--cxx', default='clang++')
    parser.add_argument('--sanitize', action='store_true')
    args = parser.parse_args()
    prove(args.out, args.runtime.resolve(), json.loads(args.fixtures.read_text()), args.cxx, args.sanitize)
