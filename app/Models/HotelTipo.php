<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class HotelTipo extends Model { protected $table='hotel_tipo'; protected $primaryKey='idhotel_tipo'; public $incrementing=true; protected $keyType='int'; protected $fillable=['nombre','estado_trash','user_trash','user_created','user_updated']; public function hoteles(): HasMany { return $this->hasMany(Hotel::class,'idhotel_tipo','idhotel_tipo'); } }