<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\News\StoreNewsRequest;
use App\Http\Requests\News\UpdateNewsRequest;
use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Support\ImageStorage;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * GET /api/news (público, ordenado del más reciente al más viejo)
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);

        $news = News::query()->orderBy('date', 'desc')->paginate($perPage);

        return NewsResource::collection($news);
    }

    /**
     * GET /api/news/{news} (público)
     */
    public function show(News $news)
    {
        return new NewsResource($news);
    }

    /**
     * POST /api/news (solo admin)
     */
    public function store(StoreNewsRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = ImageStorage::store($request->file('image'), 'news');
        }

        $news = News::create($data);

        return (new NewsResource($news))
            ->additional(['message' => 'Noticia creada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/news/{news} (solo admin)
     */
    public function update(UpdateNewsRequest $request, News $news)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $oldImage = $news->image;
            $data['image'] = ImageStorage::store($request->file('image'), 'news');
            ImageStorage::delete($oldImage);
        }

        $news->update($data);

        return (new NewsResource($news))
            ->additional(['message' => 'Noticia actualizada correctamente.']);
    }

    /**
     * DELETE /api/news/{news} (solo admin)
     */
    public function destroy(News $news)
    {
        ImageStorage::delete($news->image);
        $news->delete();

        return response()->json([
            'message' => 'Noticia eliminada correctamente.',
        ]);
    }
}
