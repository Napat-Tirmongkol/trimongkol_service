<?php

namespace App\Exceptions\Accounting;

/** Attempt to edit/delete a posted journal, its lines, or the activity log. */
class ImmutableLedgerException extends LedgerException
{
}
