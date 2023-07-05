<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('people_id');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name');
            $table->date('birth_date')->nullable();
            $table->string('ind_num')->nullable();
            $table->string('blood')->nullable();
            $table->string('tel_num')->nullable();
            $table->string('mill_permit')->nullable(); // посвідчення
            $table->string('mill_ticket')->nullable(); // військовий квиток
            $table->string('per_token')->nullable(); // жетон
            $table->string('ubd')->nullable();
            $table->date('ubd_date')->nullable();
            $table->string('driving_lic')->nullable();
            $table->date('driving_lic_date')->nullable();
            $table->json('driving_lic_cat')->nullable();
            $table->json('birth_address')->nullable();
            $table->json('reg_address')->nullable();
            $table->json('home_address')->nullable();
            $table->json('family')->nullable();
            $table->string('edu')->nullable();
            $table->string('religion')->nullable();
            $table->string('photo', 255)->nullable();
            $table->date('change_date');
            $table->longText('edited')->nullable();
            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
