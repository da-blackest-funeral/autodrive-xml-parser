<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Group extends Model
    {
        use HasFactory;

        protected $fillable = ['id', 'name'];

        public $timestamps = false;

        public function elements() {
            return $this->belongsToMany(Element::class);
        }

        public function vehicles() {
            return $this->belongsToMany(Vehicle::class);
        }
    }
