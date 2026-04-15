<?php
declare(strict_types=1);

namespace Scpp\S2S\Support;

class S2SException extends \RuntimeException
{
}

final class InputException extends S2SException
{
}

final class BuildException extends S2SException
{
}

class GenerationException extends S2SException
{
}

final class UnsupportedFeatureException extends GenerationException
{
}

final class LoweringException extends GenerationException
{
}
