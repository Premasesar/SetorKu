<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception domain untuk kegagalan validasi bisnis pada alur OAuth Google (PBI-15).
 * Pesan exception aman ditampilkan langsung kepada pengguna dalam Bahasa Indonesia.
 */
class GoogleAuthException extends RuntimeException
{
}
