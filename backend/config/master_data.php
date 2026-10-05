<?php

use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\CourseLevelResource;
use App\Http\Resources\Api\V1\EngineeringDisciplineResource;
use App\Http\Resources\Api\V1\PublicationTypeResource;
use App\Http\Resources\Api\V1\ResourceTypeResource;
use App\Http\Resources\Api\V1\TagResource;
use App\Http\Resources\Api\V1\TopicResource;
use App\Models\Category;
use App\Models\CourseLevel;
use App\Models\EngineeringDiscipline;
use App\Models\PublicationType;
use App\Models\ResourceType;
use App\Models\Tag;
use App\Models\Topic;

return [
    'categories_contexts' => ['general', 'article', 'book', 'resource', 'publication'],
    'types' => [
        'engineering-disciplines' => ['model' => EngineeringDiscipline::class, 'resource' => EngineeringDisciplineResource::class, 'table' => 'engineering_disciplines', 'ordered' => true, 'cached' => true],
        'categories' => ['model' => Category::class, 'resource' => CategoryResource::class, 'table' => 'categories', 'ordered' => true, 'cached' => false],
        'topics' => ['model' => Topic::class, 'resource' => TopicResource::class, 'table' => 'topics', 'ordered' => true, 'cached' => false],
        'tags' => ['model' => Tag::class, 'resource' => TagResource::class, 'table' => 'tags', 'ordered' => false, 'cached' => true],
        'course-levels' => ['model' => CourseLevel::class, 'resource' => CourseLevelResource::class, 'table' => 'course_levels', 'ordered' => true, 'cached' => true],
        'resource-types' => ['model' => ResourceType::class, 'resource' => ResourceTypeResource::class, 'table' => 'resource_types', 'ordered' => true, 'cached' => true],
        'publication-types' => ['model' => PublicationType::class, 'resource' => PublicationTypeResource::class, 'table' => 'publication_types', 'ordered' => true, 'cached' => true],
    ],
];
