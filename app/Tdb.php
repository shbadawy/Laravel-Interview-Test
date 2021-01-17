<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tdb extends Model
{
  public $timestamps = false;
  protected $fillable = ['name','hits', 'translation','idNumber',];
  protected $table = 'tdbs';
  protected $primaryKey = 'name';
  protected $keyType = 'string';
}
