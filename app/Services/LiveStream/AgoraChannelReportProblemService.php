<?php

namespace App\Services\LiveStream;

use Exception;
use App\Models\User;
use App\Models\Agora\AgoraChannel;
use App\Models\Morph\ReportProblem;
use App\Models\Fake\ReportProblemCategory;
use Illuminate\Database\Eloquent\Collection;

class AgoraChannelReportProblemService
{
    public function report(User $user, array $input): ReportProblem
    {
        $agoraChannel = AgoraChannel::find($input['agora_channel_id']);
        if (!$agoraChannel) throw new Exception('Yayın bulunamadı.');

        $problemCategory = ReportProblemCategory::find($input['report_problem_category_id']);
        if (!$problemCategory) throw new Exception('Geçersiz problem kategorisi.');

        return ReportProblem::create([
            'entity_type' => 'AgoraChannel',
            'entity_id' => $agoraChannel->id,
            'user_id' => $user->id,
            'status_id' => ReportProblem::STATUS_PENDING,
            'report_problem_category_id' => $problemCategory->id,
            'message' => $input['message'],
        ]);
    }

    public function myReports(User $user): Collection
    {
        return ReportProblem::query()
            ->where('user_id', $user->id)
            ->where('entity_type', 'AgoraChannel')
            ->get();
    }
}
