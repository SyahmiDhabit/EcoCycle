<?php

namespace App\Models;

/**
 * Primary Unified Model for the single 'listing' table.
 * Used for both Inventory items and Marketplace listings.
 */
class Listing extends MarketplaceListing
{
    protected $table = 'listing';
}
