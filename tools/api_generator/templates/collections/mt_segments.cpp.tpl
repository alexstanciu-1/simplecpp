#pragma once
#include "scpp/mt_segment.hpp"

namespace scpp::mt {
// A written entry covers the next count jobs in submission order.
struct job_entry { size_t count; int64_t workers; };

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
    template<class Hash>
    static segment borrow_hash_read(const Hash& hash, int64_t offset, int64_t length) {
        segment view{hash.mt_live(), 0, hash.size(), offset, length, true};
        view.hash_owner = &hash;
        view.field = [](const void* owner, int64_t position, bool key) {
            return static_cast<const Hash*>(owner)->mt_field(position, key);
        };
        return view;
    }
    template<class Hash>
    static segment borrow_hash(const Hash&, int64_t, int64_t) = delete;
    template<class Hash>
    static segment borrow_hash(Hash& hash, int64_t offset, int64_t length) {
        auto view = borrow_hash_read(hash, offset, length);
        view.read_only = false;
        return view;
    }
private:
    const segment* inputs;
    size_t count;
    const void* context{};
    union {
        int64_t (*run)(const segment*);
        int64_t (*run_values)(const segment*, const void*);
    };
    friend void join(job*, size_t, int64_t, const job_entry*, size_t);
    friend struct batch_admission;
};
void join(job* jobs, size_t count);
void join(job* jobs, size_t count, int64_t workers);
void join(job* jobs, size_t count, int64_t workers, const job_entry* entries, size_t entry_count);
}
