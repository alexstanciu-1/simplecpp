# Concrete module target depends on its publication's core; never the reverse.
function(scpp_add_datetime target core generated)
  add_library(${target} STATIC ${generated}/src/datetime.cpp
    ${PROJECT_SOURCE_DIR}/runtime/src/datetime/datetime.cpp)
  target_link_libraries(${target} PUBLIC ${core})
  target_include_directories(${target} PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/datetime)
  target_compile_features(${target} PUBLIC cxx_std_23)
  find_package(Threads REQUIRED)
  target_link_libraries(${target} PRIVATE Threads::Threads)
endfunction()
