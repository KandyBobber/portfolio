<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'states';
    protected $hidden = [
        'roles'
    ];
    protected $casts = [
        'inflected_words' => 'array',
        'roles' => 'array',
    ];
    public $filters = [
        'peopleList' => [
            ['colName' => 'num', 'requestName' => 'stateNum'],
            [
                ['colName' => 'position', 'requestName' => 'stateName', 'type' => 'like'],
                ['colName' => 'squad', 'requestName' => 'stateName', 'type' => 'like'],
                ['colName' => 'platoon', 'requestName' => 'stateName', 'type' => 'like'],
                ['colName' => 'company', 'requestName' => 'stateName', 'type' => 'like'],
                ['colName' => 'battalion', 'requestName' => 'stateName', 'type' => 'like'],
                ['colName' => 'base', 'requestName' => 'stateName', 'type' => 'like'],

            ],
            ['colName' => 'rankRound', 'requestName' => 'roundState'],
        ],
        'statesFilters' => [
            ['colName' => 'active', 'requestName' => 'date', 'type' => '<='],
            [
                ['colName' => 'no_active', 'requestName' => 'date', 'type' => '>'],
                ['colName' => 'no_active', 'value' => null],
            ],
            [
                ['colName' => 'position', 'requestName' => 'stateName', 'type' => 'like'],
                ['colName' => 'squad', 'requestName' => 'stateName', 'type' => 'like'],
                ['colName' => 'platoon', 'requestName' => 'stateName', 'type' => 'like'],
            ],
            ['colName' => 'base'], 
            ['colName' => 'company'], 
            ['colName' => 'num'],
            ['colName' => 'stateRound'],
            ['colName' => 'stateRank'],
            ['colName' => 'delete'],
        ],
        'statesPeopleFilters' => [
            ['colName' => 'pibLong', 'requestName' => 'pibFind', 'type' => 'like'], 
            ['colName' => 'rankShort'],
            ['colName' => 'service_type'],
        ],
        'actualPeople' => [
            ['colName' => 'change_date', 'requestName' => 'date', 'type' => '<='], 
        ],
    ];
    protected $guarded = [];

    protected $fillable = [
        'num',
        'rank_id',
        'position',
        'squad',
        'platoon',
        'company',
        'battalion',
        'base',
        'short_pos',
        'inflected_words',
        'tariff',
        'roles',
        'round_state_id',
        'active',
        'no_active',
    ];

    public function people()
    {
        return $this->morphToMany(People::class, 'people_morph')
        ->withTimestamps()
        ->withPivot('id', 'order', 'change_date', 'edited', 'deleted_at');
    }

    public function subjects()
    {
        # code...
    }

    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
}
