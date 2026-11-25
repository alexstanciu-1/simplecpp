// Native witnesses only: these declarations do not extend PHS struct eligibility.
#include <scpp/shared_p.hpp>
#include <scpp/unique_p.hpp>
#include <scpp/weak_p.hpp>
#include <cassert>
#include <cstdio>
#include <string>
#include <vector>

namespace {
std::vector<std::string> events;

struct traced {
    int id;
    explicit traced(int value) : id(value) { events.push_back("+" + std::to_string(id)); }
    traced(const traced& other) : id(other.id) { events.push_back("c" + std::to_string(id)); }
    traced& operator=(const traced& other) {
        events.push_back("a" + std::to_string(other.id));
        id = other.id;
        return *this;
    }
    ~traced() { events.push_back("-" + std::to_string(id)); }
};

struct nested { traced first{1}; traced second{2}; };
struct aggregate { nested child; traced last{3}; };

struct fails {
    fails() { events.push_back("throw"); throw 1; }
    ~fails() { events.push_back("unexpected failed-field destructor"); }
};
struct partial {
    traced first{1};
    fails second;
    ~partial() { events.push_back("unexpected containing destructor"); }
};

void expect(std::initializer_list<std::string> expected) {
    assert(events == std::vector<std::string>(expected));
    events.clear();
}

// Defaulted containing operations recurse in declaration order; destruction reverses it.
void composition() {
    {
        aggregate original;
        expect({"+1", "+2", "+3"});
        aggregate copy = original;
        expect({"c1", "c2", "c3"});
        copy = original;
        expect({"a1", "a2", "a3"});
    }
    expect({"-3", "-2", "-1", "-3", "-2", "-1"});

    try { partial value; assert(false); }
    catch (int) {}
    expect({"+1", "throw", "-1"});
}

// Handle liveness and pointee ownership are separate; weak.lock creates an owner.
void handles() {
    scpp::weak_p<traced> weak;
    {
        scpp::shared_p<traced> owner(std::make_shared<traced>(4));
        weak = owner;
        auto alias = owner;
        assert(owner.use_count() == 2);
        auto pinned = weak.lock();
        assert(owner.use_count() == 3);
        owner.reset();
        alias.reset();
        assert(pinned.use_count() == 1);
        assert(pinned.get()->id == 4);
        expect({"+4"});
    }
    expect({"-4"});
    assert(!weak.lock());
    weak.reset();
    expect({});

    static_assert(!std::is_copy_constructible_v<scpp::unique_p<traced>>);
    {
        scpp::unique_p<traced> source(std::make_unique<traced>(5));
        auto target = std::move(source);
        assert(!source && target);
        scpp::unique_p<traced> replacement(std::make_unique<traced>(6));
        replacement = std::move(target);
        assert(!target && replacement);
        expect({"+5", "+6", "-6"});
    }
    expect({"-5"});
}
}

int main() {
    composition();
    handles();
    std::puts("composition, partial construction, shared/weak and unique: OK");
}
