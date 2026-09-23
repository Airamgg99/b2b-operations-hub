<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'plan_name', 'status', 'starts_at', 'ends_at'];

    public function company() {
        return $this->belongsTo(Company::class);
    }
}
