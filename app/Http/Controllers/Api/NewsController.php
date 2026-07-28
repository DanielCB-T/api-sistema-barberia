<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;

class NewsController extends Controller
{
    public function index()
    {
        return NewsResource::collection(News::query()->orderBy('date', 'desc')->get());
    }
    
    public function show(News $news)
    {
        return new NewsResource($news);
    }
}
