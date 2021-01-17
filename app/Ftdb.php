<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ftdb extends Model
{
  public $timestamps = false;
  protected $table = 'ftdbs';
  protected $fillable = ['word','translation',];
  protected $primaryKey = 'word';
  protected $keyType = 'string';
}
