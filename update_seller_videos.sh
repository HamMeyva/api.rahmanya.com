#!/bin/bash
# Update existing seller videos to have is_sport=true and trending_score=1000000

cd /Users/tahir/Documents/rahmanya-backend/api.rahmanya.com

echo "=== Updating seller videos in MongoDB ==="

# Run PHP script to update videos using tinker
php artisan tinker --execute="
\$updatedCount = \App\Models\Video::where('user_id', 'like', '%seller_%')
    ->orWhere(function(\$q) {
        \$q->whereNotNull('product_id');
    })
    ->update([
        'is_sport' => true,
        'trending_score' => 1000000
    ]);

echo \"Updated {\$updatedCount} seller videos with is_sport=true and trending_score=1000000\";
"

echo ""
echo "=== Clearing all caches ==="
php artisan cache:clear
php artisan config:clear

echo ""
echo "=== Done! ==="
