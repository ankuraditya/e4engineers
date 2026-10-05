<?php

namespace App\Http\Controllers\Api\V1\Admin;

final class ResourceTypeController extends AdminMasterDataController
{
    public function __construct()
    {
        parent::__construct('resource-types');
    }
}
