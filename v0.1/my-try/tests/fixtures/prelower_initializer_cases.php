<?php
// Host-side proof data for the shared emitted-expression harness.
$initializer_program = <<<'PHS'
function mark(int &$n, int $digit): int { $n = $n * 10 + $digit; return $digit; }
function failure(): void { $v vector<receipt> = [init_step(), init_step(), init_step()]; }
function keyed_failure(): void { $v hash<receipt,int> = [1 => init_step(), 2 => init_step(), 3 => init_step()]; }
function duplicate(): void { $v hash<receipt,int> = [1 => init_step(), 1 => init_step(), 2 => init_step()]; }
function conversion_failure(): void { $v vector<int8> = [(int8)init_number(), (int8)init_number(), (int8)init_number()]; }
function key_conversion_failure(): void { $v hash<receipt,int> = [(int8)init_number() => init_step(), (int8)init_number() => init_step()]; }
function heap_failure(): void { $v vector<receipt> = [HEAP_ENTRIES]; }
function work(): int {
 $n = 0;
 $table hash<int,int> = [mark($n, 1) => mark($n, 2), mark($n, 3) => mark($n, 4)];
 if ($n !== 1234) { return 1; }
 if ($table[3] !== 4) { return 2; }
 $small vector<int8> = [(int8)3, (int8)4];
 $nested vector<vector<int>> = [[1, 2], [3, 4, 5]];
 if ($nested[1][2] !== 5) { return 3; }
 $payload = init_step();
 $list vector<receipt> = [$payload, $payload];
 $keyed hash<receipt> = ["a" => $payload, "b" => $payload];
 $receipts vector<vector<receipt>> = [[$payload], [$payload, $payload]];
 $before = init_allocations();
 $large vector<receipt> = [HEAP_ENTRIES];
 if (count($large) !== 40) { return 4; }
 return 0;
}
measure_begin();
$answer = work();
measure_end();
return $answer;
PHS;
$initializer_program = str_replace('HEAP_ENTRIES', implode(', ', array_fill(0, 40, 'init_step()')), $initializer_program);
$initializer_provider = <<<'CPP'
#include <cstring>
extern bool init_fail_allocation;
inline int init_steps = 0;
inline int init_numbers = 0;
inline long init_expected_allocation = -1;
inline long init_trace[256][4];
inline void init_reset() { begin(); init_steps=0; init_numbers=0; init_expected_allocation=-1; }
inline void init_report(const char* label) {
 report(label);
 std::printf("events %d", init_steps);
 for(int i=0;i<init_steps;++i) for(int j=0;j<4;++j) std::printf(" %ld", init_trace[i][j]);
 std::printf("\n");
}
inline runtime_type_test::receipt init_step() {
 require(init_steps<256);
 if(init_expected_allocation>=0) { require(counts.allocations==init_expected_allocation); init_expected_allocation=-1; }
 init_trace[init_steps][0]=counts.allocations;
 init_trace[init_steps][1]=counts.copied;
 init_trace[init_steps][2]=counts.moved;
 init_trace[init_steps][3]=live_values;
 ++init_steps;
 return runtime_type_test::receipt{};
}
inline scpp::int_t<> init_allocations() { init_expected_allocation=counts.allocations+1; return scpp::int_t<>{counts.allocations}; }
inline scpp::int_t<> init_number() { return scpp::int_t<>{++init_numbers==1 ? 7 : 128}; }
void function_conversion_failure();
void function_key_conversion_failure();
void function_failure();
void function_keyed_failure();
void function_heap_failure();
void function_duplicate();
inline void measure_begin() { init_reset(); }
inline void measure_end() {
 require(live_values==0);
 init_report("expressions");
 for(int keyed=0;keyed!=2;++keyed) {
  bool completed=false;
  for(int budget=0;budget<64;++budget) {
   init_reset(); copies_before_throw=budget;
   try { if(keyed) function_keyed_failure(); else function_failure(); completed=true; }
   catch(const CopyFailure&) {}
   copies_before_throw=-1;
   require(live_values==0);
   init_report(keyed ? "keyed-copy-budget" : "list-copy-budget");
   if(completed) break;
  }
  require(completed);
 }
 init_reset(); init_fail_allocation=true;
 bool allocation_failed=false;
 try { function_heap_failure(); } catch(const std::bad_alloc&) { allocation_failed=true; }
 require(allocation_failed && init_steps==0 && live_values==0);
 init_report("buffer-allocation-failure");
 for(int keyed=0;keyed!=2;++keyed) {
  init_reset(); bool conversion_failed=false;
  try { if(keyed) function_key_conversion_failure(); else function_conversion_failure(); }
  catch(const std::exception&) { conversion_failed=true; }
  require(conversion_failed && init_numbers==2 && init_steps==(keyed ? 1 : 0) && live_values==0);
  init_report(keyed ? "key-conversion-failure" : "value-conversion-failure");
 }
 init_reset(); bool duplicate_failed=false;
 try { function_duplicate(); } catch(const std::runtime_error& error) { duplicate_failed=std::strcmp(error.what(), "data initialization: duplicate key")==0; }
 require(duplicate_failed && init_steps==3 && live_values==0);
 init_report("factory-duplicate-failure");
}
CPP;
foreach (['keyed_failure', 'heap_failure', 'conversion_failure', 'key_conversion_failure'] as $name) {
    $initializer_provider = str_replace('function_' . $name, \scpp\compiler\CPP_Generator::source_name('function', $name), $initializer_provider);
}
