<?php

namespace App\Models;

use App\Traits\DefaultEntity;
use App\Traits\Filterable;
use App\Traits\OptionModel;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use Filterable, DefaultEntity, OptionModel;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'group';
    protected $primaryKey = 'group_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'group_code',
        'group_name',
        'group_icon',
        'group_link',
        'group_sort',
    ];

    protected $filterable = [
        'group_code',
        'group_name',
    ];

    protected $sortable = [
        'group_code',
        'group_name',
        'group_icon',
        'group_link',
    ];

    public static function field_name()
    {
        return 'group_name';
    }

    public function rules($id = null)
    {
        $rules = [
            'group_code' => ['required'],
            'group_name' => ['required'],
        ];

        return $rules;
    }
}
