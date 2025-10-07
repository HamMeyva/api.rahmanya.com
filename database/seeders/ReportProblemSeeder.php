<?php

namespace Database\Seeders;

use App\Models\Video;
use Illuminate\Database\Seeder;
use App\Models\Morph\ReportProblem;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ReportProblemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */use WithoutModelEvents;
    public function run(): void
    {
        if (Schema::hasTable('report_problems') && ReportProblem::query()->doesntExist()) {
            $video = Video::query()->first();
            $user = User::query()->first();
            if(!$video || !$user) return;

            $video->reported_problems()->create([
                'user_id' => $user->id,
                'status_id' => ReportProblem::STATUS_PENDING,
                'report_problem_category_id' => 1,
                'message' => 'Garip bir içerik olmuş.',
            ]);
        }
    }
}
