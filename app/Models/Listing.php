<?php

namespace App\Models;

/**
 * Backwards compatibility alias for the unified 'item' model.
 */
class Listing extends Item
{
    protected $table = 'item';
}
