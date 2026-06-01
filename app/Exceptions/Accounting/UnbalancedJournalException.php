<?php

namespace App\Exceptions\Accounting;

/** Debit ≠ Credit, a non-positive total, or a line that is not one-sided. */
class UnbalancedJournalException extends LedgerException
{
}
