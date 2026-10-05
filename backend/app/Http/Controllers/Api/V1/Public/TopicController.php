<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Services\MasterDataCache;

final class TopicController extends PublicMasterDataController
{
    public function __construct(MasterDataCache $cache)
    {
        parent::__construct('topics', $cache);
    }
}
