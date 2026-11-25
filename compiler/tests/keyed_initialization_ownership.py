"""Prove keyed factory presizing, equality, partial cleanup and private-input ownership."""
import argparse
import json
from pathlib import Path
from list_initialization_native import ROOT, includes, run
from list_initialization_ownership import PRELUDE, expression


def prove(out, fixtures, cxx, sanitize, quick=False):
    out.mkdir(parents=True, exist_ok=True)
    runtime = ROOT / 'runtime'
    # Test-only visibility: inspect the actual generated storage, without changing production headers.
    header = out / 'scpp/hashes.hpp'
    header.parent.mkdir(exist_ok=True)
    header.write_text((runtime / 'generated/public/scpp/hashes.hpp').read_text().replace('private:', 'public:'))
    emitted = expression(fixtures['php-copies']).replace('var_text_0', 'source')
    large = expression(fixtures['php-heap-input']).replace('var_text_0', 'source')
    source = '#include <cstddef>\n' + PRELUDE + '\n#include "' + str(runtime / 'generated/src/hash.cpp') + '"\n#include <cstring>\n'
    source += r'''
static bool equal(const scpp::string& a, const scpp::string& b) {
    return a.size() == b.size() && std::memcmp(a.data(), b.data(), static_cast<std::size_t>(a.size())) == 0;
}
int main() {
    const auto initial = live;
    using H = scpp::hash_int64_int64;
    using S = scpp::hash_string_string;
    { auto before = allocations; auto empty = H::from_entries(nullptr, 0); assert(empty.size() == 0); assert(allocations == before); }
    for (int count : {1, 2, 3, 4, 63, 64, 65, 255, 256, 257, 1024, 65536, 65537}) {
        auto* input = new H::keyed_entry[count];
        for (int i = 0; i < count; ++i) input[i] = {i, i * 7};
        for (bool consume : {false, true}) {
            auto before = allocations;
            auto value = consume ? H::from_owned_entries(input, count) : H::from_entries(input, count);
            assert(allocations - before == 5); // storage, entries, live slots, controls, buckets: no growth during fill
            assert(value.size() == count && value.storage_->used == static_cast<unsigned>(count));
            assert(value.storage_->capacity >= static_cast<unsigned>(count));
            assert(value.storage_->index.capacity() >= static_cast<unsigned>(count) * 2);
            const unsigned expected_width = count <= 256 ? 1 : count <= 65536 ? 2 : 4;
            assert(value.storage_->index.index_bytes() == expected_width);
            assert(reinterpret_cast<std::uintptr_t>(value.storage_->data) % alignof(H::storage::entry) == 0);
            for (int i = 0; i < count; ++i) assert(value.at(scpp::int64{i}) == i * 7);
            value.set(scpp::int64{0}, scpp::int64{99}); assert(value.size() == count && value.at(scpp::int64{0}) == 99);
        }
        delete[] input;
    }
    for (scpp::int64 count : {-1L, 2147483648L, 9223372036854775807L}) {
        const auto before = live;
        try { auto invalid = H::from_entries(nullptr, count); (void)invalid; assert(false); }
        catch (const std::length_error&) {}
        assert(live == before);
    }
    // Different keys sharing the same candidate fingerprint/bucket must both survive.
    scpp::int64 collision = 1;
    while ((scpp::detail::hash_integer(collision) & 127) != (scpp::detail::hash_integer(0) & 127)) ++collision;
    { H::keyed_entry input[]{{0, 7}, {collision, 9}}; auto value = H::from_entries(input, 2); assert(value.at(scpp::int64{0}) == 7 && value.at(collision) == 9); }
    {
        scpp::string source("heap backed value with embedded\0bytes for copy", 45);
        const auto expected = source;
        S::keyed_entry input[]{{scpp::string("a"), source}, {scpp::string("b"), source}};
        auto copied = S::from_entries(input, 2);
        assert(equal(input[0].value, expected) && equal(source, expected));
        auto moved = S::from_owned_entries(input, 2);
        assert(equal(moved.at(scpp::string("b")), expected));
        copied.set(scpp::string("a"), scpp::string("changed"));
        assert(equal(moved.at(scpp::string("a")), expected));
        // Copy-mode and consume-mode failures preserve an already existing destination.
        for (bool consume : {false, true}) {
            bool succeeded = false;
            for (long fail = 0; fail < 100 && !succeeded; ++fail) {
                S::keyed_entry fresh[]{{scpp::string("long heap key with more than twenty two bytes"), source}, {scpp::string("b"), source}};
                auto before = live;
                budget = fail;
                try { auto value = consume ? S::from_owned_entries(fresh, 2) : S::from_entries(fresh, 2); succeeded = true; }
                catch (const std::bad_alloc&) {}
                budget = -1;
                // Moved fresh entries may release payloads; equality with the external source is the invariant.
                assert(live <= before && equal(source, expected));
            }
            assert(succeeded);
        }
        S::keyed_entry duplicate[]{{scpp::string("same"), source}, {scpp::string("same"), source}};
        auto before = live;
        try { moved = S::from_entries(duplicate, 2); assert(false); }
        catch (const std::invalid_argument& e) { assert(std::strcmp(e.what(), "duplicate_initializer_key") == 0); }
        assert(live == before && equal(moved.at(scpp::string("a")), expected));
        bool succeeded = false;
        for (long fail = 0; fail < 100 && !succeeded; ++fail) {
            auto previous = moved;
            auto baseline = live;
            budget = fail;
            try { moved = EMITTED; succeeded = true; }
            catch (const std::bad_alloc&) {}
            budget = -1;
            assert(equal(source, expected));
            if (!succeeded) assert(live == baseline && equal(moved.at(scpp::string("a")), previous.at(scpp::string("a"))));
        }
        assert(succeeded);
    }
    {
        scpp::string source("heap backed value with embedded\0bytes for copy", 45);
        const auto expected = source;
        bool succeeded = false;
        for (long fail = 0; fail < 400 && !succeeded; ++fail) {
            auto before = live;
            budget = fail;
            try { auto value = LARGE; assert(value.size() == 65 && equal(value.at(scpp::string("long heap-backed key number 64")), expected)); succeeded = true; }
            catch (const std::bad_alloc&) {}
            budget = -1;
            assert(live == before);
        }
        assert(succeeded);
    }
    assert(live == initial);
}
'''.replace('EMITTED', emitted).replace('LARGE', large)
    unit = out / 'proof.cpp'
    unit.write_text(source)
    flags = ['-std=c++23', '-O0' if quick else '-O1', '-g0' if quick else '-g', '-Wall', '-Wextra', '-Werror', '-I', out, *includes(runtime)]
    if sanitize: flags += ['-fsanitize=address,undefined', '-fsanitize-address-use-after-scope', '-fno-omit-frame-pointer']
    run([cxx, *flags, unit, runtime / 'generated/src/runtime.cpp', '-o', out / 'proof'])
    run([out / 'proof'])
    print(cxx, 'PASS keyed capacity/index/ownership/allocation failure proofs', 'ASan/UBSan' if sanitize else '')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('--fixtures', type=Path)
    parser.add_argument('--cxx', default='clang++')
    parser.add_argument('--sanitize', action='store_true')
    parser.add_argument("--quick", action="store_true")
    args = parser.parse_args()
    fixtures = args.fixtures
    if fixtures is None or not fixtures.exists():
        args.out.mkdir(parents=True, exist_ok=True)
        fixtures = args.out / 'fixtures.json'
        run(['php', ROOT / 'compiler/tests/keyed_initialization.php', fixtures])
    prove(args.out.resolve(), json.loads(fixtures.read_text()), args.cxx, args.sanitize, args.quick)
