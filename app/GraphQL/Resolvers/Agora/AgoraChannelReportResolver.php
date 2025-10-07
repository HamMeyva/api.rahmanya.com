<?php

namespace App\GraphQL\Resolvers\Agora;

use Exception;
use App\Services\LiveStream\AgoraChannelReportProblemService;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class AgoraChannelReportResolver
{
    protected $agoraChannelReportService;

    public function __construct(AgoraChannelReportProblemService $agoraChannelReportService)
    {
        $this->agoraChannelReportService = $agoraChannelReportService;
    }

    public function report($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $authUser = $context->user();
        $input = $args['input'];

        try {
            $report = $this->agoraChannelReportService->report($authUser, $input);

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
            $reports = $this->agoraChannelReportService->myReports($authUser);

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
