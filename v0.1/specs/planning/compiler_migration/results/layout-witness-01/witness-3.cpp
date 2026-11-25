struct alignas(16) t1 { unsigned char bytes[32]; };
extern "C" const unsigned long long size = sizeof(t1);
extern "C" const unsigned long long alignment = alignof(t1);
