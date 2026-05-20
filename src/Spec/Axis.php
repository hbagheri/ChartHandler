<?php

namespace HBVSoft\ChartHandler\Spec;

/**
 * Which value axis a series is plotted against. Combo charts can mix both: bars on the
 * left axis, a line on an independently-scaled right axis, for example.
 */
enum Axis
{
    case Left;
    case Right;
}
