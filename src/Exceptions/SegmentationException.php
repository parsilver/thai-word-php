<?php

namespace Farzai\ThaiWord\Exceptions;

use Exception;

class SegmentationException extends Exception
{
    // Input validation error codes (1000-1999)
    public const INPUT_EMPTY = 1001;

    public const INPUT_INVALID_ENCODING = 1002;

    public const INPUT_TOO_LONG = 1003;

    // Dictionary error codes (2000-2999)
    public const DICTIONARY_NOT_LOADED = 2001;

    public const DICTIONARY_FILE_NOT_FOUND = 2002;

    public const DICTIONARY_INVALID_FORMAT = 2003;

    public const DICTIONARY_INVALID_SOURCE = 2004;

    public const DICTIONARY_DOWNLOAD_FAILED = 2005;

    public const DICTIONARY_EMPTY = 2006;

    public const DICTIONARY_WRITE_FAILED = 2007;

    // Algorithm error codes (3000-3999)
    public const ALGORITHM_NOT_FOUND = 3001;

    public const ALGORITHM_PROCESSING_FAILED = 3002;

    // Configuration error codes (4000-4999)
    public const CONFIG_INVALID = 4001;

    public const CONFIG_MISSING_REQUIRED = 4002;

    // System error codes (5000-5999)
    public const SYSTEM_MEMORY_LIMIT = 5001;

    public const SYSTEM_TIME_LIMIT = 5002;
}
