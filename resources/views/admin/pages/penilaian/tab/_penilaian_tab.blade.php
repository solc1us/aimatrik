<h5>Domain</h5>
@php
    $columns = 3;
    $total = count($getAllDomainQuestion);
    $perColumn = max(1, (int) ceil($total / $columns));
    $chunks = array_chunk($getAllDomainQuestion->sortBy('id')->values()->all(), $perColumn);
@endphp

<div class="row mb-4">
    @foreach ($chunks as $chunk)
        <div class="col-md-4">
            @foreach ($chunk as $index => $itemQuestion)
                <div class="mt-2">
                    <a href="{{ route('admin.daftar-penilaian.penilaian.index', [
                        'id_daftar_penilaian' => $findDaftarPenilaian->id,
                        'page' => $itemQuestion->id,
                    ]) }}"
                        class="btn {{ request()->page == $itemQuestion->id || (empty(request()->page) && $loop->parent->first && $loop->first) ? 'btn-primary' : 'btn-outline-primary' }} icon icon-left col-12"
                        style="text-align: left;">
                        @if ($loop->iteration % 2 == 1)
                            <i class="bi bi-bookmark-heart-fill"></i>
                        @else
                            <i class="bi bi-bookmark-star-fill"></i>
                        @endif
                        {{ $itemQuestion->domain?->code . ' - ' . $itemQuestion->domain?->name }}
                    </a>
                </div>
            @endforeach
        </div>
    @endforeach
</div>


<hr>
