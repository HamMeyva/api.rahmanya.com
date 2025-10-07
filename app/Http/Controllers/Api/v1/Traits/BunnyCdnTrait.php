<?php

namespace App\Http\Controllers\Api\v1\Traits;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

trait BunnyCdnTrait
{

    public function listVideos(Request $request)
    {
        return $this->bunny->listVideos();
    }


    public function getVideoPlayData(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'videoId' => 'required',
        ]);

        if ($validate->fails()) {
            return response([
                'errors' => $validate->errors()],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->bunny->getVideoPlayData($request->videoId);
    }

    public function collections(Request $request)
    {

        return $this->bunny->getVideoCollections();
    }


    public function updateVideoData($videoId, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'title' => 'required',
            'isPrivate' => 'nullable',
            'language' => 'required',
            'location' => 'required',
            'description' => 'required',
            'tags' => 'required|array',
            //TODO: yorumlara izin ver ve maça izin ver boolean eklenecek (isPrivate olayı ise herkese izin ver, takipçilerim, takip ettiğim takipçiler, hiç kimse mevzusu nasıl olacak?)
        ]);

        if ($validate->fails()) {
            return response([
                'success' => false,
                'response' => $validate->errors()
            ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $dataSet = [
            'title' => $request->title,
            'collectionId' => $request->user()->collection_uuid,
            'metaTags' => collect([
                'userId' => (string)$request->user()->id,
                'nickname' => $request->user()->nickname,
                'primaryTeamName' => $request->user()->primary_team?->name ?? 'belirtilmemiş',
                'isPrivate' => ($request->isPrivate) ? "true" : "false",
                'language' => $request->language,
                'location' => $request->location,
                'title' => $request->title,
                'description' => $request->description,
                'tags' => collect($request->tags)->implode(', '),
            ])->map(function ($value, $key) {
                return [
                    'property' => $key,
                    'value' => $value,
                ];
            })->values(),
        ];

        $body = json_encode($dataSet, JSON_THROW_ON_ERROR, JSON_PRETTY_PRINT);

        $responseData = $this->bunny->updateVideoData(videoId: $videoId, dataSet: $body);

        $video = Video::query()
            ?->updateOrCreate([
                'collection_uuid' => $request->user()->collection_uuid,
                'video_guid' => $videoId,
            ], [
                'user_id' => $request->user()->id,
                'name' => $request->title,
                'description' => $request->description,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
                'is_private' => ($request->isPrivate == 'false') ? false : true,

                'parameters' => $responseData,
            ]);


        return response()->json([
            'success' => true,
            'response' => $responseData,
            'message' => "Video updated successfully.",
        ]);
    }

}
