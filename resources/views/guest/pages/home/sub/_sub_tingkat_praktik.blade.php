
<section class="aim-practice-section" id="tingkat-praktik">
    <div class="container">
        <div class="aim-practice-panel">
            <div class="aim-practice-grid"></div>

            <div class="row align-items-end g-5 position-relative">
                <div class="col-lg-7 aim-reveal">
                    <span class="aim-section-kicker"><i></i> PRACTICE DIRECTORY / 03</span>
                    <h2>Praktik dalam<br><span>Model AIMATRIK.</span></h2>
                </div>

                <div class="col-lg-5 aim-reveal">
                    <p class="aim-practice-description">
                        Berikut merupakan daftar praktik yang terdapat dalam setiap domain
                        Model Tata Kelola AI di Pendidikan Tinggi (AIMATRIK). Praktik-praktik
                        ini dirancang untuk memandu institusi dalam menerapkan tata kelola AI
                        secara sistematis, terukur, dan berkelanjutan.
                    </p>

                    <a href="https://drive.google.com/file/d/1U-gwoTVGn5JiPgcNW4IUh0LsTa_R7Zg4/view?usp=sharing"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="aim-practice-link">
                        <span>
                            <small>EXPLORE THE FRAMEWORK</small>
                            Lihat Daftar Lengkap Praktik
                        </span>
                        <span class="aim-practice-arrow">↗</span>
                    </a>
                </div>
            </div>

            <div class="aim-practice-footer">
                <span>AI GOVERNANCE FRAMEWORK</span>
                <span>SYSTEMATIC · MEASURABLE · SUSTAINABLE</span>
            </div>
        </div>
    </div>
</section>

<style>
    .aim-practice-section {
        --aim-red: #ed1b2f;
        padding: 85px 0;
        background: #f7f8fa;
    }

    .aim-practice-panel {
        position: relative;
        overflow: hidden;
        padding: clamp(30px, 6vw, 75px);
        color: #fff;
        background: #11161c;
        isolation: isolate;
    }

    .aim-practice-grid {
        position: absolute;
        z-index: -1;
        inset: 0;
        opacity: .22;
        pointer-events: none;
        background-image:
            linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
        background-size: 42px 42px;
        mask-image: linear-gradient(110deg, #000, transparent 90%);
    }

    .aim-practice-section .aim-section-kicker {
        color: #9ca5ae;
    }

    .aim-practice-section h2 {
        margin: 0;
        font-size: clamp(43px, 6.5vw, 82px);
        font-weight: 800;
        line-height: .98;
        letter-spacing: -.075em;
    }

    .aim-practice-section h2 span {
        color: var(--aim-red);
    }

    .aim-practice-description {
        margin: 0 0 25px;
        color: #b0b8c0;
        font-size: 14px;
        line-height: 1.9;
    }

    .aim-practice-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 20px 0;
        color: #fff;
        border-top: 1px solid rgba(255,255,255,.17);
        border-bottom: 1px solid rgba(255,255,255,.17);
        text-decoration: none;
        transition: color .3s ease;
    }

    .aim-practice-link small {
        display: block;
        margin-bottom: 8px;
        color: #929ba5;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .14em;
    }

    .aim-practice-link > span:first-child {
        font-size: 14px;
        font-weight: 600;
    }

    .aim-practice-arrow {
        display: grid;
        flex-shrink: 0;
        place-items: center;
        width: 42px;
        height: 42px;
        color: #fff;
        background: var(--aim-red);
        font-size: 19px;
        transition: transform .3s ease;
    }

    .aim-practice-link:hover {
        color: var(--aim-red);
    }

    .aim-practice-link:hover .aim-practice-arrow {
        transform: translate(3px, -3px);
    }

    .aim-practice-footer {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 55px;
        padding-top: 18px;
        border-top: 1px solid rgba(255,255,255,.12);
        color: #79838e;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .12em;
    }

    @media (max-width: 575px) {
        .aim-practice-section { padding: 55px 0; }
        .aim-practice-footer { margin-top: 35px; }
    }
</style>
