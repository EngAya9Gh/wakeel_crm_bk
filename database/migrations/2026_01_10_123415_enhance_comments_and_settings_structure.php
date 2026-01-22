<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Comment Types (Control by user)
        Schema::create('comment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        // 2. Enhance Comments Table
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('user_id')->constrained('comment_types')->onDelete('set null');
            $table->string('subject')->nullable()->after('content'); // Optional subject
        });

        // 3. Mentions in Comments
        Schema::create('comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // 4. Link Files to Comments (Attachments)
        Schema::table('client_files', function (Blueprint $table) {
            $table->foreignId('comment_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('client_files', function (Blueprint $table) {
            $table->dropForeign(['comment_id']);
            $table->dropColumn('comment_id');
        });
        
        Schema::dropIfExists('comment_mentions');

        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn(['type_id', 'subject']);
        });

        Schema::dropIfExists('comment_types');
    }
};
