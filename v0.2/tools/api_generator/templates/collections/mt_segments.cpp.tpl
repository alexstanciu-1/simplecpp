#pragma once
#include "scpp/mt_segment.hpp"

namespace scpp::mt {
struct job {
    // The caller owns the grant array through the synchronous join.
    template<size_t N>
    job(const segment (&grants)[N], int64_t (*worker)(const segment*))
        : inputs(grants), count(N), run(worker) {}

    void execute() { result = run(inputs); }
    int64_t result{};

    template<class Vector>
    static segment borrow(Vector& vector, int64_t offset, int64_t length) {
        auto* data = vector.mt_data();
        return {reinterpret_cast<unsigned char*>(data), sizeof(*data), vector.size(), offset, length};
    }
private:
    const segment* inputs;
    size_t count;
    int64_t (*run)(const segment*);
    friend void join(job*, size_t);
    static void admit(const job*, size_t);
};
void join(job* jobs, size_t count);
}
