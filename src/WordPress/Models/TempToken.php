<?php
namespace FatPanda\Illuminate\WordPress\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class TempToken extends Eloquent {

  protected $fillable = ['type', 'token'];

  static function factory($type, $token) 
  {
    return self::firstOrCreate(['type' => $type, 'token' => $token]);
  }

  function getIdAttribute()
  {
    return 'tt:'.$this->attributes['id'];
  }

  static function is($id)
  {
    
    if (stripos($id, 'tt:') === 0) {
      return self::find(substr($id, 3));
    } else {
      return false;
    }
  }

}