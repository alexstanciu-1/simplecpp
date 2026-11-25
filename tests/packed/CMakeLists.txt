add_executable(packed_storage_test storage.cpp identity.cpp)
target_include_directories(packed_storage_test PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/hash)
target_link_libraries(packed_storage_test PRIVATE scpp_packed_storage)
add_test(NAME packed_storage COMMAND packed_storage_test)
