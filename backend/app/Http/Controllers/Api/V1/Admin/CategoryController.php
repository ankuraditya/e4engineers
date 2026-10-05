<?php

namespace App\Http\Controllers\Api\V1\Admin;

final class CategoryController extends AdminMasterDataController
{
    public function __construct()
    {
        parent::__construct('categories');
    }
}
