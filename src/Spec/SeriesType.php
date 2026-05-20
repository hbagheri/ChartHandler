<?php

namespace HBVSoft\ChartHandler\Spec;

/**
 * How an individual series is drawn within a combo chart. (For single-type charts the
 * chart's ChartType decides this; in a Combo each series picks its own.)
 */
enum SeriesType
{
    case Bar;
    case Line;
    case Area;
}
