<?php

namespace App\Exceptions\Accounting;

/** No period covers the date, or the period is closed/locked. */
class ClosedPeriodException extends LedgerException
{
}
