<?php

namespace App\GraphQL\Resolvers\Video;

use Exception;
use App\Models\Video;
use App\Models\Morph\ReportProblem;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use App\Models\Fake\ReportProblemCategory;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class VideoReportResolver
{
    public function report($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $input = $args['input'];

        try {
            $video = Video::find($input['video_id']);
            if (!$video)
                throw new Exception('Video bulunamadı.');

            if ($video->user_id == $authUser->id)
                throw new Exception('Kendi videolarınızı rapor edemezsiniz.');

            $existingReport = ReportProblem::query()
                ->where('entity_type', 'Video')
                ->where('entity_id', $video->id)
                ->where('user_id', $authUser->id)
                ->first();
            if ($existingReport)
                throw new Exception('Bu video zaten rapor edildi.');

            $categoryId = $input['report_problem_category_id'] ?? null;
            if ($categoryId) {
                $problemCategory = ReportProblemCategory::find($categoryId);
                if (!$problemCategory)
                    throw new Exception('Geçersiz problem kategorisi.');
            }

            $report = ReportProblem::create([
                'entity_type' => 'Video',
                'entity_id' => $video->id,
                'user_id' => $authUser->id,
                'status_id' => ReportProblem::STATUS_PENDING,
                'report_problem_category_id' => $categoryId,
                'message' => $input['message'],
            ]);

            $video->increment('report_count');

            return [
                'success' => true,
                'message' => 'Rapor başarıyla gönderildi.',
                'data' => $report
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function myReports($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();

        try {
            $reports = ReportProblem::query()
                ->where('user_id', $authUser->id)
                ->where('entity_type', 'Video')
                ->get();

            return [
                'success' => true,
                'message' => 'Raporlar başarıyla alındı.',
                'data' => $reports
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
