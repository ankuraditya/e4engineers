<?php

namespace App\Http\Controllers\Api\V1\Admin;

final class PublicationTypeController extends AdminMasterDataController
{
    public function __construct()
    {
        parent::__construct('publication-types');
    }
}
