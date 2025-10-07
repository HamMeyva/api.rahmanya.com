<?php

namespace App\Observers;

use App\Models\Ad\Advertiser;
use App\Services\BunnyCdnService;

class AdvertiserObserver
{
    public function created(Advertiser $advertiser): void
    {
        $this->createCollection($advertiser);
    }


    protected function createCollection(Advertiser $advertiser): void
    {
        $bunnyCdnService = app(BunnyCdnService::class);
        $collectionUuid = $bunnyCdnService->createCollection($advertiser->id);

        $advertiser->collection_uuid = $collectionUuid;
        $advertiser->save();
    }
}
