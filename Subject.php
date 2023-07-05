<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

/**
 * Summary of Subject
 */
class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subjects';
    protected $casts = [
        'driving_lic_cat' => 'array',
        'birth_address' => 'array',
        'reg_address' => 'array',
        'home_address' => 'array',
        'family' => 'array'
    ];
    protected $guarded = [];

    /**
     * Вводиться ім'я фільтрів, як ім'я масиву, в самому масиві вже вносяться дані в такому вигляді як вказано нижче requestName є не обов'язковим, type теж
     * ['colName' => '', 'requestName' => '', 'type' => ''],
     * 'type' - type of collation '%like%', '>', '<', '=', default '='
     * @var array
     */
    public $filters = [
        'peopleList' => [
            [
                ['colName' => 'last_name', 'requestName' => 'pibFind', 'type' => 'like'], 
                ['colName' => 'first_name', 'requestName' => 'pibFind', 'type' => 'like'], 
                ['colName' => 'middle_name', 'requestName' => 'pibFind', 'type' => 'like'], 
            ]
        ],
        'actualPeople' => [
            ['colName' => 'change_date', 'requestName' => 'date', 'type' => '<='], 
        ],

    ];

    public static $translateTable = [
        'id' => 'id',
        'people_id' => 'id особи',
        'last_name' => 'пізвище',
        'first_name' => 'ім\'я',
        'middle_name' => 'по батькові',
        'birth_date' => 'дата народження',
        'ind_num' => 'РНОКПП',
        'blood' => 'група крові',
        'tel_num' => 'номер телефону',
        'mill_permit' => 'посвідчення',
        'mill_ticket' => 'військовий квиток',
        'per_token' => 'жетон',
        'ubd' => 'номер УБД',
        'ubd_date' => 'дата видачі УБД',
        'driving_lic' => 'номер прав',
        'driving_lic_date' => 'дата видачі прав',
        'driving_lic_cat' => 'категорії',
        'birth_address' => 'адреса народження',
        'reg_address' => 'адреса прописки',
        'home_address' => 'адреса проживання',
        'family' => 'сім\'я',
        'edu' => 'освіта',
        'religion' => 'віросповідання',
        'photo' => 'фото',
        'change_date' => 'дата зміни',
        'edited' => 'редагування'
    ];

    /**
     * Summary of notСomparison, використовується щоб відділити поля які не потрібно порівнювати
     * @var array
     */
    public static $notСomparison = [
        'id', 'people_id', 'edited', 'created_at', 'updated_at', 'photo', 'change_date'
    ];
    
    public function people()
    {
        return $this->belongsTo(People::class);
    }

    /**
     * Getting columns from table
     * @param string $table
     * @return array
     */
    public static function getTableColumns($table = false)
    {
        if (!empty($table)) {
            return Schema::getColumnListing($table);
        } else {
            return Schema::getColumnListing((new self())->table);
        }
    }
}
