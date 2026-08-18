<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class HotelHabitacion extends Model { protected $table='hotel_habitacion'; protected $primaryKey='idhotel_habitacion'; public $incrementing=true; protected $keyType='int'; protected $fillable=['idhotel','nombre','cant_huespeds','precio_coorporativo','precio_normal','precio_temp_alta','descripcion','estado_trash','user_trash','user_created','user_updated']; public function hotel(): BelongsTo { return $this->belongsTo(Hotel::class,'idhotel','idhotel'); } }