<?php

namespace App\Http\Controllers\Api\V1\Admin;

final class TagController extends AdminMasterDataController
{
    public function __construct()
    {
        parent::__construct('tags');
    }
}
