<?php

namespace HBVSoft\ChartHandler\Exception;

use Throwable;

/**
 * Marker interface implemented by every exception this library throws, so callers
 * can catch all ChartHandler failures with a single `catch (ChartHandlerException $e)`.
 */
interface ChartHandlerException extends Throwable
{
}
