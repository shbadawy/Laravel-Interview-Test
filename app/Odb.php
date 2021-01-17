<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Odb extends Model
{
  public $timestamps = false;
  protected $table = 'odbs';
  protected $fillable = ['name', 'hits','idNumber',];
}
