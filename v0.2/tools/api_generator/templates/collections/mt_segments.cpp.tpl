#pragma once
#include "scpp/mt_segment.hpp"

namespace scpp::mt {
struct job {
    int64_t (*run)(segment, segment);
    segment inputs[2];
    int64_t result{};

    template<class Vector>
    static segment borrow(Vector& vector, int64_t offset, int64_t length) {
        auto* data = vector.mt_data();
        return {reinterpret_cast<unsigned char*>(data), sizeof(*data), vector.size(), offset, length};
    }
private:
    friend void join(job*, size_t);
    static void admit(const job*, size_t);
};
void join(job* jobs, size_t count);
}
