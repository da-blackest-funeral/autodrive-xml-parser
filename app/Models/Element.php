<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Element extends Model
    {
        use HasFactory;

        protected $fillable = ['id', 'name'];

        public $timestamps = false;

        public function groups() {
            return $this->belongsToMany(Group::class);
        }
    }
