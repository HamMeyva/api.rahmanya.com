<?php

namespace App\GraphQL\Resolvers;

use Exception;
use App\Models\Morph\ReportProblem;
use GraphQL\Type\Definition\ResolveInfo;
use App\Models\Fake\ReportProblemCategory;
use App\Models\GeneralProblem;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class GeneralReportResolver
{
    public function report($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $input = $args['input'];

        try {
            $problemCategory = ReportProblemCategory::find($input['report_problem_category_id']);
            if (!$problemCategory) throw new Exception('Geçersiz problem kategorisi.');

            $generalProblem = GeneralProblem::create([]);

            ReportProblem::create([
                'entity_type' => 'GeneralProblem',
                'entity_id' => $generalProblem->id,
                'user_id' => $authUser->id,
                'status_id' => ReportProblem::STATUS_PENDING,
                'report_problem_category_id' => $problemCategory->id,
                'message' => $input['message'],
            ]);

            return [
                'success' => true,
                'message' => 'Rapor başarıyla gönderildi.',
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
                ->where('entity_type', 'GeneralProblem')
                ->get();

            return [
                'success' => true,
                'message' => 'Raporlar başarıyla alındı.',
                'data' => $reports,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
