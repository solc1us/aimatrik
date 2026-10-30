<section class="aim-structure" id="struktur">
    <div class="container">
        <div class="row g-5 align-items-start">
            <!-- Kolom Kiri: Struktur Model -->
            <div class="col-lg-6 aim-reveal-left">
                <div class="aim-structure-heading text-start mb-4">
                    <div class="aim-structure-meta justify-content-start">
                        <span>03</span>
                        <span class="aim-structure-line"></span>
                        <span>AIMATRIK / FRAMEWORK</span>
                    </div>

                    <h2>Struktur Model <span>AIMATRIK.</span></h2>

                    <p>
                        Model ini mengadopsi struktur hierarkis dari model CMMI,
                        yang disesuaikan untuk mendukung penerapan tata kelola
                        kecerdasan buatan di lingkungan pendidikan tinggi.
                    </p>
                </div>

                <div class="aim-structure-image">
                    <div class="aim-image-topline">
                        <span>AIMATRIK FRAMEWORK</span>
                        <span>MODEL STRUCTURE / 01</span>
                    </div>

                    <img
                        src="{{ asset('assets/images/logo/asset-model.png') }}"
                        alt="Diagram struktur model AIMATRIK"
                        loading="lazy">

                    <div class="aim-image-caption">
                        <span>AI GOVERNANCE FRAMEWORK</span>
                        <span>HIGHER EDUCATION</span>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: 6 Domain Utama -->
            <div class="col-lg-6 aim-reveal-right">
                <div class="aim-domain-intro mb-4" id="domain-utama">
                    <span class="aim-section-kicker justify-content-start"><i></i> FRAMEWORK COMPONENTS / 01</span>
                    <h2>6 Domain Utama dalam Model <span>AIMATRIK.</span></h2>
                </div>

                <div class="aim-domain-content-wrapper">
                 

                    <div class="aim-domain-list-heading">
                        <span>DOMAIN DIRECTORY</span>
                        <span>01 — 06</span>
                    </div>

                    <div class="accordion aim-domain-accordion" id="accordionExample">
                        @include('guest.pages.home.collapse._one_collapse')
                        @include('guest.pages.home.collapse._two_collapse')
                        @include('guest.pages.home.collapse._three_collapse')
                        @include('guest.pages.home.collapse._four_collapse')
                        @include('guest.pages.home.collapse._five_collapse')
                        @include('guest.pages.home.collapse._six_collapse')
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Tingkatan Level --}}
@include('guest.pages.home.sub._sub_tingkatan_level')

{{-- Praktik Tata Kelola AI --}}
@include('guest.pages.home.sub._sub_tingkat_praktik')

{{-- Profil Pengembang dan Pembimbing --}}
@include('guest.pages.home.sub._sub_profile')

{{-- Footer --}}
@include('guest.pages.home.sub._sub_footer')

<style>
    .aim-structure {
        --aim-red: #ed1b2f;
        overflow: hidden;
        padding: 85px 0 100px;
        color: #161b21;
        background: #f7f8fa;
    }

    .aim-structure-heading h2,
    .aim-domain-intro h2 {
        margin: 0 0 16px;
        font-size: clamp(28px, 3.2vw, 42px);
        font-weight: 800;
        letter-spacing: -.055em;
        line-height: 1.15;
    }

    .aim-structure-heading h2 span,
    .aim-domain-intro h2 span {
        color: var(--aim-red);
    }

    .aim-structure-meta,
    .aim-section-kicker {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        color: #7d858e;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .15em;
    }

    .aim-structure-meta span:first-child {
        color: var(--aim-red);
        font-size: 14px;
    }

    .aim-structure-line {
        width: 34px;
        height: 1px;
        background: var(--aim-red);
    }

    .aim-section-kicker i {
        width: 7px;
        height: 7px;
        background: var(--aim-red);
        border-radius: 50%;
    }

    .aim-structure-heading p {
        color: #727b85;
        font-size: 14px;
        line-height: 1.8;
        margin-bottom: 0;
    }

    /* Card untuk Kolom Kanan (Tema Gelap khas Domain Utama) */
    .aim-domain-content-wrapper {
        background: #11161c;
        padding: 24px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,.08);
        box-shadow: 0 20px 55px rgba(0,0,0,.15);
    }

    .aim-structure-image {
        padding: 16px;
        overflow: hidden;
        border: 1px solid #e3e6ea;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 20px 55px rgba(17, 22, 28, .07);
    }

    .aim-domain-image {
        padding: 16px;
        background: #f7f8fa;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 12px;
    }

    .aim-image-topline,
    .aim-image-caption,
    .aim-domain-image-label,
    .aim-domain-image-footer,
    .aim-domain-list-heading {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        color: #7d858e;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .12em;
    }

    .aim-domain-image-label,
    .aim-domain-image-footer,
    .aim-domain-list-heading {
        color: #89929d;
    }

    .aim-image-topline {
        padding: 5px 3px 12px;
    }

    .aim-structure-image img,
    .aim-domain-image img {
        display: block;
        width: 100%;
        height: auto;
        object-fit: contain;
    }

    .aim-image-caption {
        margin-top: 12px;
        padding: 12px 3px 3px;
        border-top: 1px solid #eceef1;
    }

    .aim-domain-image-footer {
        padding-top: 12px;
        margin-top: 10px;
        border-top: 1px solid #e1e4e8;
    }

    .aim-domain-list-heading {
        padding: 16px 0 12px;
        color: #929ba5;
    }

    .aim-domain-accordion {
        border-top: 1px solid rgba(255,255,255,.16);
    }

    .aim-domain-accordion .accordion-item {
        color: #f7f8fa;
        background: transparent;
        border: 0;
        border-bottom: 1px solid rgba(255,255,255,.14);
    }

    .aim-domain-accordion .accordion-button {
        gap: 12px;
        padding: 18px 6px;
        color: #f7f8fa;
        background: transparent;
        box-shadow: none;
        font-size: 13px;
        font-weight: 600;
    }

    .aim-domain-accordion .accordion-button::after {
        filter: invert(1);
        opacity: .65;
    }

    .aim-domain-accordion .accordion-button:not(.collapsed) {
        color: var(--aim-red);
    }

    .aim-domain-accordion .accordion-button:not(.collapsed)::after {
        filter: none;
        opacity: 1;
    }

    .aim-domain-accordion .accordion-body {
        padding: 0 18px 20px 24px;
        color: #adb5be;
        font-size: 12px;
        line-height: 1.8;
    }

    .aim-domain-accordion .accordion-body p:last-child {
        margin-bottom: 0;
    }

    @media (max-width: 991.98px) {
        .aim-structure {
            padding: 55px 0 70px;
        }
    }
</style>