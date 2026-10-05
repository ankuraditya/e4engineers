<?php

namespace App\Http\Controllers\Api\V1\Admin;

final class EngineeringDisciplineController extends AdminMasterDataController
{
    public function __construct()
    {
        parent::__construct('engineering-disciplines');
    }
}
