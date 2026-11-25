#pragma once

struct lifetime_counts {
    int constructed = 0;
    int destroyed = 0;
    int released = 0;
};

// Count native object lifetimes separately from releasing the transferred source value.
struct tracked_result {
    lifetime_counts& counts;
    bool owns = true;
    explicit tracked_result(lifetime_counts& counts) noexcept : counts(counts) { ++counts.constructed; }
    tracked_result(tracked_result&& other) noexcept : counts(other.counts), owns(other.owns) {
        other.owns = false;
        ++counts.constructed;
    }
    ~tracked_result() {
        ++counts.destroyed;
        if (owns) ++counts.released;
    }
};
