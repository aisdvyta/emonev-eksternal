<?php

namespace App\Services\Drive;

use RuntimeException;

/** Pesan Drive tidak valid (rusak, asing, atau rahasia salah): dibuang, bukan diulang. */
class MessageException extends RuntimeException
{
}
