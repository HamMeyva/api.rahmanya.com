@use('App\Helpers\CommonHelper')
@props([
    'videos' => [],
])

@if ($videos->isEmpty())
    <tr>
        <td colspan="6" class="text-center">
            <span class="text-gray-500 fw-semibold fs-6">Veri bulunamadı</span>
        </td>
    </tr>
@else
    @foreach ($videos as $item)
        @php
            $user = $item->user();
        @endphp
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-3">
                        <img src="{{ $item->thumbnailUrl }}" class="" alt="" />
                    </div>
                    <div class="d-flex justify-content-start flex-column">
                        <a target="_blank" href="{{ route('admin.videos.show', ['id' => $item->id]) }}"
                            class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">{{ (new CommonHelper())->limitText($item->title) }}</a>
                        <span
                            class="text-gray-500 fw-semibold d-block fs-7">{{ (new CommonHelper())->limitText($item->description) }}</span>
                    </div>
                </div>
            </td>
            <td>{!! $user?->nickname ?? '<em>Bulunamadı</em>' !!}</td>
            <td class="text-center">
                <span class="badge badge-secondary badge-lg">{{ (new CommonHelper())->formatNumber($item->views_count, 'dot') }}</span>
            </td>
            <td class="text-center">
                <a href="{{ route('admin.videos.show', ['id' => $item->id]) }}"
                    class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px">
                    <i class="ki-duotone ki-black-right fs-2 text-gray-500"></i>
                </a>
            </td>
        </tr>
    @endforeach
@endif
