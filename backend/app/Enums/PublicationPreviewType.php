<?php

namespace App\Enums;

enum PublicationPreviewType: string
{
    case Text = 'text';
    case Media = 'media';
    case SampleFileReference = 'sample-file-reference';
    case None = 'none';
}
