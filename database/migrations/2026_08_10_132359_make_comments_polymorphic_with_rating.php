<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('comments') || ! Schema::hasColumn('comments', 'post_id')) {
            return;
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->uuid('commentable_id')->nullable()->after('id');
            $table->string('commentable_type')->nullable()->after('commentable_id');
            $table->string('first_name')->nullable()->after('user_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone', 20)->nullable()->after('last_name');
            $table->unsignedTinyInteger('rating')->nullable()->after('body');
            $table->index(['commentable_type', 'commentable_id']);
        });

        foreach (DB::table('comments')->orderBy('id')->get() as $comment) {
            DB::table('comments')->where('id', $comment->id)->update([
                'commentable_id' => $comment->post_id,
                'commentable_type' => Post::class,
                'first_name' => filled($comment->author_name) ? $comment->author_name : 'کاربر',
                'last_name' => '-',
                'phone' => null,
                'rating' => 5,
            ]);
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_id');
            $table->dropColumn(['author_name', 'email']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('comments') || Schema::hasColumn('comments', 'post_id')) {
            return;
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->foreignUuid('post_id')->nullable()->after('id')->constrained('posts')->cascadeOnDelete();
            $table->string('author_name')->nullable()->after('body');
            $table->string('email')->nullable()->after('author_name');
        });

        foreach (DB::table('comments')->where('commentable_type', Post::class)->orderBy('id')->get() as $comment) {
            DB::table('comments')->where('id', $comment->id)->update([
                'post_id' => $comment->commentable_id,
                'author_name' => trim(($comment->first_name ?? '').' '.($comment->last_name ?? '')),
                'email' => null,
            ]);
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['commentable_type', 'commentable_id']);
            $table->dropColumn([
                'commentable_id',
                'commentable_type',
                'first_name',
                'last_name',
                'phone',
                'rating',
            ]);
        });
    }
};
