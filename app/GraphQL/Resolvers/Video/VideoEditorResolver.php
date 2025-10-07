<?php

namespace App\GraphQL\Resolvers\Video;

use Exception;
use App\Models\Video;
use App\Models\Music\Music;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class VideoEditorResolver
{
    public function storeVideoFromEditor($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        try {
            $authUser = $context->user();

            $input = $args['input'];

            $http = Http::asMultipart();

            // 1. Videoları attach et
            foreach ($input['videos'] as $video) {
                if ($video instanceof UploadedFile) {
                    $tempFileName = rand(1, 99999999999999) . '_' . time() . '_' . rand(1, 99999999999999) . '_' . $video->getClientOriginalName();
                    $video->storeAs('temp', $tempFileName, 'public');

                    $tempFilePath = storage_path('app/public/temp/' . $tempFileName);
                    $tempFileContents = file_get_contents($tempFilePath);

                    $fileName = rand(1, 1000000) . '_' . time() . '_' . $video->getClientOriginalName();
                    $http = $http->attach("videos[]", $tempFileContents, $fileName);

                    unlink($tempFilePath);
                }
            }

            // 2. Audio dosyalarını attach et
            $audios = $input['embed_audios'] ?? null;
            foreach ($audios as $index => $audio) {
                $music = Music::find($audio['music_id']);
                if (!$music) {
                    return [
                        'success' => false,
                        'message' => 'Müzik sistemde bulunamadı.',
                    ];
                }

                $input['embed_audios'][$index]['music_url'] = $music->music_url;
            }

            //3. Video kaydı oluştur.
            $videoData = [
                'user_id' => $authUser->id,
                'collection_uuid' => $authUser->collection_uuid,
            ];

            if ($input['thumbnail_image']) {
                $file = $input['thumbnail_image'];

                $fileName =  time() . '_' . rand(1, 1000000) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('temp/thumbnails', $fileName, 'public');
                $videoData['temp_thumbnail_image'] = "temp/thumbnails/{$fileName}";
            }else{
                $videoData['temp_thumbnail_duration'] = $input['thumbnail_duration'] ?? 0;
            }
            $video = Video::create($videoData);

            // 4. JSON verileri stringleştirerek ekle
            $response = $http->post(env('VIDEO_PROCESSOR_SERVICE_URL') . '/api/video/process', [
                'user_id' => $authUser->id,
                'video_id' => $video->id,
                'callback_url' => route('video-process.callback'),
                'embed_texts' => isset($input['embed_texts']) ? json_encode($input['embed_texts']) : null,
                'embed_audios' => isset($input['embed_audios']) ? json_encode($input['embed_audios']) : null,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json('message'),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response->json('message'),
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Beklenmedik bir hata oluştu. Hata mesajı: ' . $e->getMessage(),
            ];
        }
    }

    public function downloadVideoFromEditor($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        try {
            $authUser = $context->user();

            $input = $args['input'];

            $http = Http::asMultipart();

            // 1. Videoları attach et
            foreach ($input['videos'] as $video) {
                if ($video instanceof UploadedFile) {
                    $tempFileName = rand(1, 99999999999999) . '_' . time() . '_' . rand(1, 99999999999999) . '_' . $video->getClientOriginalName();
                    $video->storeAs('temp', $tempFileName, 'public');

                    $tempFilePath = storage_path('app/public/temp/' . $tempFileName);
                    $tempFileContents = file_get_contents($tempFilePath);

                    $fileName = rand(1, 1000000) . '_' . time() . '_' . $video->getClientOriginalName();
                    $http = $http->attach("videos[]", $tempFileContents, $fileName);

                    unlink($tempFilePath);
                }
            }

            // 2. Audio dosyalarını attach et
            $audios = $input['embed_audios'] ?? [];
            foreach ($audios as $index => $audio) {
                $music = Music::find($audio['music_id']);
                if (!$music) {
                    return [
                        'success' => false,
                        'message' => 'Müzik sistemde bulunamadı.',
                    ];
                }

                $input['embed_audios'][$index]['music_url'] = $music->music_url;
            }

            // 3. JSON verileri stringleştirerek ekle
            $response = $http->post(env('VIDEO_PROCESSOR_SERVICE_URL') . '/api/video/download', [
                'user_id' => $authUser->id,
                'callback_url' => route('video-process.callback'),
                'embed_texts' => isset($input['embed_texts']) ? json_encode($input['embed_texts']) : null,
                'embed_audios' => isset($input['embed_audios']) ? json_encode($input['embed_audios']) : null,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json('message'),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response->json('message'),
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Beklenmedik bir hata oluştu. Hata mesajı: ' . $e->getMessage(),
            ];
        }
    }
}
