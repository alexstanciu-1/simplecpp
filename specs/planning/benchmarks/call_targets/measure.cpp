// The generated application unit is included by the driver.
#include <algorithm>
#include <array>
#include <chrono>
#include <cstdio>
#include <cstdlib>
#include <new>
#include <type_traits>
#include <variant>
#include <vector>

namespace {
std::size_t allocations, allocated_bytes;
void* allocate(std::size_t bytes) {
    if (void* p = std::malloc(bytes ? bytes : 1)) {
        ++allocations;
        allocated_bytes += bytes;
        return p;
    }
    throw std::bad_alloc();
}
}
void* operator new(std::size_t n) { return allocate(n); }
void* operator new[](std::size_t n) { return allocate(n); }
void operator delete(void* p) noexcept { std::free(p); }
void operator delete[](void* p) noexcept { std::free(p); }
void operator delete(void* p, std::size_t) noexcept { std::free(p); }
void operator delete[](void* p, std::size_t) noexcept { std::free(p); }

namespace {
using namespace App;
using Selection = scpp_records::Selection;
enum class Role { empty, source, runtime, method, local, construct };
struct Owners {
    owner_SymbolEntry source = owner_SymbolEntry::make();
    owner_RuntimeBinding runtime = owner_RuntimeBinding::make();
    owner_MethodInstance method = owner_MethodInstance::make();
    owner_LocalBinding local = owner_LocalBinding::make();
    owner_SymbolEntry record = owner_SymbolEntry::make();

    Owners() {
        source->role = 1; source->signature = 10; source->head = 11;
        runtime->signature = 20; runtime->nativeSymbol = 21;
        method->authored = weak_SymbolEntry(source); method->implementation = 31;
        local->signature = 40; local->binding = 41;
        record->role = 5; record->signature = 50; record->head = 51;
    }
};

#if INTERFACE
using Slot = weak_Target;
Selection read(const Slot& slot) { return readTarget_(slot); }
#else
// Separate source and construction roles borrow the same class of stable symbol entry.
struct Source { weak_SymbolEntry ref; };
struct Construct { weak_SymbolEntry ref; };
using Slot = std::variant<std::monostate, Source, weak_RuntimeBinding, weak_MethodInstance, weak_LocalBinding, Construct>;
Selection read(const Slot& slot) {
    switch (static_cast<Role>(slot.index())) {
        case Role::empty: return {};
        case Role::source: return readSymbolEntry_(std::get<Source>(slot).ref);
        case Role::runtime: return readRuntimeBinding_(std::get<weak_RuntimeBinding>(slot));
        case Role::method: return readMethodInstance_(std::get<weak_MethodInstance>(slot));
        case Role::local: return readLocalBinding_(std::get<weak_LocalBinding>(slot));
        case Role::construct: return readSymbolEntry_(std::get<Construct>(slot).ref);
    }
    std::abort();
}
#endif

Slot bind(Role role, const Owners& owners) {
    switch (role) {
        case Role::source:
#if INTERFACE
            return bindSymbolEntry_(owners.source);
#else
            return Source{bindSymbolEntry_(owners.source)};
#endif
        case Role::construct:
#if INTERFACE
            return bindSymbolEntry_(owners.record);
#else
            return Construct{bindSymbolEntry_(owners.record)};
#endif
        case Role::runtime: return bindRuntimeBinding_(owners.runtime);
        case Role::method: return bindMethodInstance_(owners.method);
        case Role::local: return bindLocalBinding_(owners.local);
        case Role::empty: return {};
    }
    std::abort();
}

void require(bool ok) { if (!ok) std::abort(); }

void verify() {
    Owners o;
    std::array<Slot, 6> slots;
    for (int i = 0; i < 6; ++i) {
        slots[i] = bind(static_cast<Role>(i), o);
        require(read(slots[i]).role == i);
    }
    o.source->head = 12;
    o.source->signature = 13;
    require(read(slots[1]).operand == 12 && read(slots[3]).signature == 13 && read(slots[3]).operand == 31);
    o.record->head = 52;
    require(read(slots[5]).operand == 52);
    slots[2] = bind(Role::source, o); // Source shadows the runtime spelling.
    require(read(slots[2]).operand == 12);
    slots[2] = bind(Role::runtime, o);
    require(read(slots[2]).operand == 21);
    Owners sibling;
    auto sibling_call = bind(Role::method, sibling);
    o.method = {};
    o.local = {};
    require(read(slots[3]).role == 0 && read(slots[4]).role == 0 && read(sibling_call).operand == 31);
    o.local = owner_LocalBinding::make();
    o.local->binding = 42;
    require(read(slots[4]).role == 0); // Replacement cannot revive the old observer.
    slots[4] = bind(Role::local, o);
    require(read(slots[4]).operand == 42);
    slots[4] = Slot{};
    require(read(slots[4]).role == 0);
}

struct Request { Role role; unsigned group; };
constexpr unsigned groups = 128, functions = 512, body_size = 64, sites = functions * body_size;
using Clock = std::chrono::steady_clock;
struct Timing { double prepare_us, consume_us; unsigned long long checksum; };

Timing sample(std::vector<Slot>& slots, const std::vector<Request>& inputs, const std::vector<Owners>& owners,
              unsigned start, unsigned count) {
    auto before = Clock::now();
    for (unsigned i = start; i < start + count; ++i)
        slots[i] = bind(inputs[i].role, owners[inputs[i].group]);
    auto prepared = Clock::now();
    unsigned long long checksum = 0;
    for (unsigned i = start; i < start + count; ++i) {
        auto selected = read(slots[i]);
        checksum += selected.role + selected.signature + selected.operand;
    }
    auto end = Clock::now();
    return {std::chrono::duration<double, std::micro>(prepared-before).count(),
            std::chrono::duration<double, std::micro>(end-prepared).count(), checksum};
}
}

int main() {
    verify();
    std::size_t base_count = allocations, base_bytes = allocated_bytes;
    std::vector<Owners> owners(groups);
    for (unsigned group = 0; group < groups; ++group) {
        owners[group].source->head += group;
        owners[group].runtime->nativeSymbol += group;
        owners[group].method->implementation += group;
        owners[group].local->binding += group;
        owners[group].record->head += group;
    }
    auto owner_count = allocations-base_count, owner_bytes = allocated_bytes-base_bytes;
    base_count = allocations; base_bytes = allocated_bytes;
    std::vector<Slot> slots(sites);
    auto slot_count = allocations-base_count, slot_bytes = allocated_bytes-base_bytes;
    std::vector<Request> requests(sites);
    unsigned state = 12345;
    auto next = [&] { state = state * 1664525u + 1013904223u; return state >> 8; };
    for (auto& r : requests) r = {static_cast<Role>(1 + next()%5), next()%groups};
    std::array<double, 41> fresh{}, edit{}, fresh_use{}, edit_use{};
    unsigned long long checksum = 0;
    base_count = allocations;
    for (int run = -1; run < 41; ++run) {
        std::fill(slots.begin(), slots.end(), Slot{});
        auto full = sample(slots, requests, owners, 0, sites);
        auto start = ((run+2)*17u % functions)*body_size;
        for (unsigned i = start; i < start+body_size; ++i)
            requests[i].group = (requests[i].group + 1) % groups;
        auto changed = sample(slots, requests, owners, start, body_size);
        checksum += full.checksum + changed.checksum;
        if (run >= 0) {
            fresh[run] = full.prepare_us; fresh_use[run] = full.consume_us;
            edit[run] = changed.prepare_us; edit_use[run] = changed.consume_us;
        }
    }
    // Independent oracle for every retained site, including the unchanged ones.
    constexpr std::array<int, 6> signatures{0, 10, 20, 10, 40, 50};
    constexpr std::array<int, 6> operands{0, 11, 21, 31, 41, 51};
    for (unsigned i = 0; i < sites; ++i) {
        auto role = static_cast<unsigned>(requests[i].role);
        auto result = read(slots[i]);
        require(result.role == role && result.signature == signatures[role] &&
                result.operand == operands[role] + static_cast<int>(requests[i].group));
    }
    auto bind_allocations = allocations-base_count;
    require(bind_allocations == 0);
    auto median = [](auto values) { std::sort(values.begin(), values.end()); return values[20]; };
    std::printf("%s slot=%zu align=%zu owner_payload_sizes=%zu/%zu/%zu/%zu owner_alloc=%zu/%zuB slot_alloc=%zu/%zuB work_alloc=%zu fresh_us=%.3f edit_us=%.3f fresh_use_us=%.3f edit_use_us=%.3f checksum=%llu\n",
        INTERFACE ? "interface" : "tagged", sizeof(Slot), alignof(Slot), sizeof(App::scpp_records::SymbolEntry),
        sizeof(App::scpp_records::RuntimeBinding), sizeof(App::scpp_records::MethodInstance), sizeof(App::scpp_records::LocalBinding),
        owner_count, owner_bytes, slot_count, slot_bytes, bind_allocations, median(fresh), median(edit), median(fresh_use), median(edit_use), checksum);
}
