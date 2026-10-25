from pathlib import Path
import sys,json
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try';out=Path('/tmp/layout-cycle-small-02');out.mkdir(exist_ok=False)
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
(out/'incomplete.cpp').write_text('''#include "modules/compiler/bindings.hpp"
#include <type_traits>
struct Node;
using Children = scpp::compiler::Storage<Node>;
static_assert(sizeof(Children) > 0);
static_assert(std::is_default_constructible_v<Children>);
static_assert(std::is_copy_constructible_v<Children>);
static_assert(std::is_move_constructible_v<Children>);
static_assert(std::is_copy_assignable_v<Children>);
static_assert(std::is_move_assignable_v<Children>);
struct Node { Children children; };
int main() { Node parent; auto child = scpp::shared_p<Node>(std::make_shared<Node>()); parent.children.append(child); return parent.children.count() == 1 ? 0 : 1; }
''')
with CommandRunner(out/'logs',root) as r:
 def native():
  r.run('native-build',['clang++-18','-std=c++23','-DSCPP_LANGUAGE_TARGET_PHP=1','-I',root/'runtime/include',out/'incomplete.cpp','-o',out/'native'],timeout=120)
  r.run('native-run',[out/'native'])
 def check(mode):
  p=out/mode;(p/'source').mkdir(parents=True)
  (p/'source/main.phs').write_text('class Node { public $children Storage<Node>; public function __construct() { $this->children = new Storage<Node>(); } } $node = new Node();')
  r.run(mode,['php8.5',app/'tests/async_tasks_publish.php',p,root/'runtime/metadata/modules','compiler',mode],expected=255 if mode == 'concrete' else 0)
  if mode == 'ordinary':
   build=json.loads(r.run('ordinary-build',['php8.5',app/'tools/build_cpp.php',p/'published',p/'cache'],timeout=300))
   r.run('ordinary-run',[build['executable']])
 results=run_tasks([Task('native',native)]+[Task(mode,lambda mode=mode:check(mode)) for mode in ['ordinary','concrete']])
(out/'summary.json').write_text(json.dumps([{'name':x.name,'error':x.error,'seconds':x.seconds} for x in results],indent=2))
