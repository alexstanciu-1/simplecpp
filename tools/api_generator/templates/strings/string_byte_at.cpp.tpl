    if (arg1 < 0 || arg1 >= arg0.size()) return -1;
    return static_cast<unsigned char>(arg0.data()[arg1]);
