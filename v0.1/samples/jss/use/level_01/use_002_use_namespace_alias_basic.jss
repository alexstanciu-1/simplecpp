namespace Lib.Math;

class Box {
	static value(): int {
		return 43;
	}
}

namespace App;

use Lib.Math as M;

print(M.Box.value(), "\n");
