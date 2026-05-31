<?php

namespace App\Exceptions\Accounting;

use RuntimeException;

/** Base for everything the ledger refuses to do. */
class LedgerException extends RuntimeException
{
}
