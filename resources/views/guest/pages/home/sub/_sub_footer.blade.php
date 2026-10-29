
<footer class="aim-footer">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5 aim-reveal">
                <a href="{{ route('guest.index') }}" class="aim-footer-brand">
                    <img src="{{  asset('assets/images/logo/logo2.png') }}"
                         alt="AIMATRIK">
                </a>

                <p class="aim-footer-description">
                    AI Maturity &amp; Governance Assessment
                </p>

                <div class="aim-footer-contact-title">HUBUNGI KAMI</div>
                <ul class="aim-footer-contact">
                    <li>
                        <span>EMAIL</span>
                        <a href="mailto:irfanwalhidayah@gmail.com">
                            irfanwalhidayah@gmail.com
                        </a>
                    </li>
                    <li>
                        <span>WEBSITE</span>
                        <a href="{{ route('guest.index') }}">
                            AIMATRIK
                        </a>
                    </li>
                    <li>
                        <span>WHATSAPP</span>
                        <a href="https://wa.me/628983197636"
                           target="_blank" rel="noopener noreferrer">
                            08983197636
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-5 aim-reveal">
                <div class="aim-footer-heading">NAVIGASI</div>
                <ul class="aim-footer-links">
                    <li><a href="#visi">Visi Kami <span>↗</span></a></li>
                    <li><a href="#mengapa">Mengapa AIMATRIK? <span>↗</span></a></li>
                    <li><a href="#struktur">Struktur Model <span>↗</span></a></li>
                    <li><a href="#domain-utama">Domain Utama <span>↗</span></a></li>
                    <li><a href="#level">Tingkatan Level Praktik <span>↗</span></a></li>
                    <li><a href="#tingkat-praktik">Praktik <span>↗</span></a></li>
                    <li><a href="#pengembang">Pengembang <span>↗</span></a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-7 aim-reveal">
                <div class="aim-footer-heading">ABOUT THE FRAMEWORK</div>
                <h3 class="aim-footer-statement">
                    Tata kelola AI yang lebih terstruktur<span>.</span>
                </h3>
                <p class="aim-footer-note">
                    Mendorong penerapan kecerdasan buatan yang terukur,
                    bertanggung jawab, dan berkelanjutan di pendidikan tinggi.
                </p>
            </div>
        </div>

        <div class="aim-footer-bottom">
            <span>© {{ date('Y') }} AIMATRIK</span>
            <span>AI MATURITY &amp; GOVERNANCE ASSESSMENT</span>
            <a href="#visi">BACK TO TOP ↑</a>
        </div>
    </div>
</footer>

<style>
    .aim-footer {
        --aim-red: #ed1b2f;
        padding: 75px 0 25px;
        color: #fff;
        background: #0c1015;
        border-top: 1px solid rgba(237,27,47,.45);
    }

    .aim-footer-brand {
        display: inline-flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .aim-footer-brand img {
        display: block;
        width: 170px;
        max-height: 80px;
        object-fit: contain;
        object-position: left center;
    }

    .aim-footer-description {
        margin-bottom: 32px;
        color: #8e98a3;
        font-size: 12px;
        letter-spacing: .03em;
    }

    .aim-footer-contact-title,
    .aim-footer-heading {
        margin-bottom: 20px;
        color: #f0f2f4;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .15em;
    }

    .aim-footer-contact {
        display: grid;
        gap: 15px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .aim-footer-contact li {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .aim-footer-contact li > span {
        color: #707b86;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .12em;
    }

    .aim-footer-contact a {
        width: fit-content;
        color: #b9c0c7;
        font-size: 12px;
        overflow-wrap: anywhere;
        transition: color .25s ease;
    }

    .aim-footer a:hover {
        color: var(--aim-red);
    }

    .aim-footer-links {
        display: grid;
        gap: 5px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .aim-footer-links a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 0;
        color: #a5aeb7;
        font-size: 12px;
        border-bottom: 1px solid rgba(255,255,255,.07);
        transition: color .25s ease, padding .25s ease;
    }

    .aim-footer-links a span {
        color: var(--aim-red);
        opacity: 0;
        transform: translateX(-5px);
        transition: opacity .25s ease, transform .25s ease;
    }

    .aim-footer-links a:hover {
        padding-left: 4px;
    }

    .aim-footer-links a:hover span {
        opacity: 1;
        transform: translateX(0);
    }

    .aim-footer-statement {
        max-width: 320px;
        margin: 0 0 16px;
        color: #f7f8fa;
        font-size: clamp(25px, 3vw, 34px);
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -.045em;
    }

    .aim-footer-statement span {
        color: var(--aim-red);
    }

    .aim-footer-note {
        max-width: 340px;
        color: #909aa4;
        font-size: 12px;
        line-height: 1.9;
    }

    .aim-footer-back {
        display: inline-flex;
        align-items: center;
        gap: 18px;
        margin-top: 12px;
        color: #f4f5f6;
        font-size: 12px;
        font-weight: 600;
    }

    .aim-footer-back span {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border: 1px solid rgba(237,27,47,.55);
        color: var(--aim-red);
        transition: background .25s ease, color .25s ease;
    }

    .aim-footer-back:hover span {
        color: #fff;
        background: var(--aim-red);
    }

    .aim-footer-bottom {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 60px;
        padding-top: 22px;
        border-top: 1px solid rgba(255,255,255,.12);
        color: #737d87;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .1em;
    }

    .aim-footer-bottom a {
        color: #aab2bb;
    }

    @media (max-width: 767px) {
        .aim-footer {
            padding-top: 55px;
        }

        .aim-footer-bottom {
            margin-top: 40px;
        }
    }
</style>
