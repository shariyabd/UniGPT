<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * SHA-256 content hash used to detect duplicate uploads (same bytes, even
     * under a different filename). Indexed, not unique: soft-deleted documents
     * may legitimately share a hash with a re-upload, and uniqueness is enforced
     * at the application layer (StoreDocumentRequest) so users get a friendly
     * validation message rather than a database error.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->after('original_filename')->index();
        });

        // Backfill existing rows (including soft-deleted) so duplicate detection
        // works against them too. Uses the query builder rather than the Document
        // model: model global scopes reference columns added by later migrations,
        // which breaks migrate:fresh.
        $disk = Storage::disk('public');

        DB::table('documents')
            ->whereNull('file_hash')
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->chunkById(1000, function ($documents) use ($disk) {
                foreach ($documents as $document) {
                    if ($disk->exists($document->file_path)) {
                        DB::table('documents')->where('id', $document->id)->update([
                            'file_hash' => hash('sha256', (string) $disk->get($document->file_path)),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['file_hash']);
            $table->dropColumn('file_hash');
        });
    }
};
