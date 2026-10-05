<?php

namespace App\Enums;

enum DigitalResourcePreviewType: string
{
    case None = 'none';
    case Text = 'text';
    case Media = 'media';
    case SampleFile = 'sample_file';
}
