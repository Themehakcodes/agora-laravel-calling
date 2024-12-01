<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name')->unique(); // Unique channel name
            $table->foreignId('caller_id')->constrained('users'); // The caller's user_id
            $table->foreignId('receiver_id')->constrained('users'); // The receiver's user_id
            $table->enum('status', ['pending', 'accepted', 'rejected', 'ended'])->default('pending'); // Call status
            $table->timestamps(); // Created and updated timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calls');
    }
}
    