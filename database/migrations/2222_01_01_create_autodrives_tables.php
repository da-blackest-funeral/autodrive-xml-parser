<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up() {
            Schema::create('dealers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('autodrive_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('models', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('generations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('body_configurations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('modifications', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('complectations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('vehicles', function (Blueprint $table) {
                $columns = json_decode(file_get_contents(storage_path('app/xml/types.json')));

                $columns = collect((array)$columns)
                    ->map(function ($item) {
                        return \Str::snake($item);
                    });
                foreach ($columns as $column => $type) {
                    $table->$type(\Str::snake($column))->nullable();
                }

                $table->foreignId('dealer_id');
                $table->foreignId('category_id')
                    ->constrained('autodrive_categories');
                $table->foreignId('brand_id');
                $table->foreignId('model_id');
                $table->foreignId('generation_id');
                $table->foreignId('body_configuration_id');
                $table->foreignId('modification_id');
                $table->foreignId('complectation_id');
            });

            Schema::create('groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });

            Schema::create('elements', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
            });

            Schema::create('element_group_vehicle', function (Blueprint $table) {
                $table->foreignId('vehicle_id');
                $table->foreignId('group_id');
                $table->foreignId('element_id');
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down() {
            Schema::dropIfExists('vehicles');
            Schema::dropIfExists('dealers');
            Schema::dropIfExists('autodrive_categories');
            Schema::dropIfExists('brands');
            Schema::dropIfExists('models');
            Schema::dropIfExists('generations');
            Schema::dropIfExists('body_configurations');
            Schema::dropIfExists('modifications');
            Schema::dropIfExists('complectations');
            Schema::dropIfExists('vehicle_group');
            Schema::dropIfExists('element_group_vehicle');
        }
    };
