<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class People extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'people';
    protected $fillable = ['no_active'];
    public $filters = [
        'peopleList' => [
            ['colName' => 'last_name', 'type' => 'like'],
        ],
    ];
    
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function ranks()
    {
        return $this->morphedByMany(Rank::class, 'people_morph')
        ->withTimestamps()
        ->withPivot('id', 'order', 'change_date', 'edited', 'service_type', 'deleted_at');
    }

    public function states()
    {
        return $this->morphedByMany(State::class, 'people_morph')
        ->withTimestamps()
        ->withPivot('id', 'order', 'change_date', 'edited', 'deleted_at');
    }

    public function peopleMorph()
    {
        return $this->hasMany(PeopleMorph::class);
    }

    public function peopleMorphRanks()
    {
        return $this->hasMany(PeopleMorph::class)
        ->where('people_morphs.people_morph_type', Rank::class)
        ->whereNull('people_morphs.deleted_at')
        ->orderBy('people_morphs.change_date', 'desc');
    }

    public function peopleMorphStates()
    {
        return $this->hasMany(PeopleMorph::class)
        ->where('people_morphs.people_morph_type', State::class)
        ->whereNull('people_morphs.deleted_at')
        ->orderBy('people_morphs.change_date', 'desc');
    }

    public function equipment()
    {
        return $this->morphedByMany(Equipment::class, 'people_morph')
        ->withTimestamps()
        ->withPivot('id', 'order', 'change_date', 'edited', 'deleted_at');
    }

    public function marks()
    {
        return $this->belongsToMany(Mark::class, 'people_mark')
        ->withTimestamps()
        ->withPivot('id', 'change_date', 'edited', 'message', 'deleted_at');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'people_role')
        ->withTimestamps()
        ->withPivot('id', 'round_state_id');
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
    

}
