<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Category extends Model
    {
        use HasFactory;

        protected $table = 'autodrive_categories';

        public $timestamps = false;

        protected $fillable = ['id', 'name'];
    }
