<div class="accordion-item">
    <h2 class="accordion-header" id="heading{{ $itemQuestionAnswerBidPraktik->id }}">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#collapse{{ $itemQuestionAnswerBidPraktik->id }}" aria-expanded="false"
            aria-controls="collapse{{ $itemQuestionAnswerBidPraktik->id }}">
            {{ $itemQuestionAnswerBidPraktik->bidangPraktik?->code . ' - ' . $itemQuestionAnswerBidPraktik->bidangPraktik?->name }}
        </button>
    </h2>
    <div id="collapse{{ $itemQuestionAnswerBidPraktik->id }}" class="accordion-collapse collapse"
        aria-labelledby="heading{{ $itemQuestionAnswerBidPraktik->id }}"
        data-bs-parent="#accordionSection-{{ $itemQuestionAnswerBidKapabilitas->id }}">
        <div class="accordion-body">
            @include('admin.pages.penilaian.question._praktik_item')
        </div>
    </div>
</div>
