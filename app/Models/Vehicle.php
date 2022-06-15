<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Vehicle extends Model
    {
        use HasFactory;

        protected $guarded = [];

        public $timestamps = false;

        public function dealer() {
            return $this->belongsTo(Dealer::class);
        }

        public function category() {
            return $this->belongsTo(Category::class);
        }

        public function brand() {
            return $this->belongsTo(Brand::class);
        }

        public function model() {
            return $this->belongsTo(CarModel::class);
        }

        public function generation() {
            return $this->belongsTo(Generation::class);
        }

        public function bodyConfiguration() {
            return $this->belongsTo(BodyConfiguration::class);
        }

        public function modification() {
            return $this->belongsTo(Modification::class);
        }

        public function complectation() {
            return $this->belongsTo(Complectation::class);
        }

        public function groups() {
            return $this->belongsToMany(Group::class);
        }
    }
