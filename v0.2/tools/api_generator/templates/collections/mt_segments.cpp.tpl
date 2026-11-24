#pragma once
#include "scpp/mt_segment.hpp"

namespace scpp::mt {
struct job {
    // The caller owns the grant array through the synchronous join.
    template<size_t N>
    job(const segment (&grants)[N], int64_t (*worker)(const segment*))
        : inputs(grants), count(N), run(worker) {}

    // Both the scalar snapshot and grants live in the caller until join returns.
    template<size_t N, class Snapshot>
    job(const segment (&grants)[N], Snapshot& snapshot, int64_t (*worker)(const segment*, const void*))
        : inputs(grants), count(N), context(&snapshot), run_values(worker) {}

    void execute() { result = context ? run_values(inputs, context) : run(inputs); }
    int64_t result{};

    template<class Vector>
    static segment borrow(Vector& vector, int64_t offset, int64_t length) {
        auto* data = vector.mt_data();
        return {reinterpret_cast<unsigned char*>(data), sizeof(*data), vector.size(), offset, length, false};
    }
    template<class Vector>
    static segment borrow_read(const Vector& vector, int64_t offset, int64_t length) {
        auto* data = vector.mt_data();
        return {reinterpret_cast<const unsigned char*>(data), sizeof(*data), vector.size(), offset, length, true};
    }
private:
    const segment* inputs;
    size_t count;
    const void* context{};
    union {
        int64_t (*run)(const segment*);
        int64_t (*run_values)(const segment*, const void*);
    };
    friend void join(job*, size_t, int64_t);
    static void admit(const job*, size_t);
};
void join(job* jobs, size_t count);
void join(job* jobs, size_t count, int64_t workers);
}
