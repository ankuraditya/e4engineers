<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Services\MasterDataCache;

final class CategoryController extends PublicMasterDataController
{
    public function __construct(MasterDataCache $cache)
    {
        parent::__construct('categories', $cache);
    }
}
