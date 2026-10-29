add_executable(mixed_storage_test storage.cpp)
target_include_directories(mixed_storage_test PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/boxed)
target_link_libraries(mixed_storage_test PRIVATE scpp_boxed_storage)
add_test(NAME mixed_storage COMMAND mixed_storage_test)
