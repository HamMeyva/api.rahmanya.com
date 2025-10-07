<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperGeneralProblem
 */
class GeneralProblem extends Model
{
    protected $connection = 'pgsql';
    protected $table = "general_problems";
}
