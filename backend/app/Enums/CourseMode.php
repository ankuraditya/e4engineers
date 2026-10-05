<?php

namespace App\Enums;

enum CourseMode: string
{
    case SelfPaced = 'self-paced';
    case InstructorLed = 'instructor-led';
    case Online = 'online';
    case Hybrid = 'hybrid';
    case Classroom = 'classroom';
}
