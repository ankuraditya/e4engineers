<?php

namespace App\Enums;

enum EnrollmentType: string
{
    case Enquiry = 'enquiry';
    case ExternalUrl = 'external-url';
    case InternalFuture = 'internal-future';
    case None = 'none';
}
